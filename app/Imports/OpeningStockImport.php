<?php

namespace App\Imports;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * OnEachRow (not ToModel) because each row is two writes — a ProductBatch
 * plus a StockMovement — mirroring OpeningStockEntry::save()'s existing
 * per-line logic exactly. product_variant_id is deliberately never set:
 * ProductBatch already uses HasDefaultProductVariant, which auto-fills it
 * from the product's default variant whenever it's blank.
 */
class OpeningStockImport implements OnEachRow, SkipsEmptyRows, SkipsOnFailure, WithHeadingRow, WithValidation
{
    use Importable, SkipsFailures;

    /**
     * @var array<string, int|null>
     */
    private array $productIdBySku = [];

    /**
     * @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, Location>>
     */
    private \Illuminate\Support\Collection $locationsByName;

    private int $importedCount = 0;

    public function __construct()
    {
        $this->locationsByName = Location::where('is_active', true)->get()
            ->groupBy(fn (Location $location) => mb_strtolower(trim($location->name)));
    }

    public function rules(): array
    {
        return [
            'sku' => [
                // No 'string' rule — see ProductsImport::rules() for why:
                // Excel commonly stores a numeric-looking SKU as a number.
                'required',
                fn ($attribute, $value, $fail) => $this->resolveProductId($value) === null
                    && $fail(__('opening_stock_entry.import.sku_not_found', ['sku' => $value])),
            ],
            'location' => [
                'required', 'string',
                function ($attribute, $value, $fail) {
                    $matches = $this->locationsByName->get(mb_strtolower(trim($value))) ?? collect();

                    if ($matches->isEmpty()) {
                        $fail(__('opening_stock_entry.import.location_not_found', ['location' => $value]));
                    } elseif ($matches->count() > 1) {
                        $fail(__('opening_stock_entry.import.location_ambiguous', ['location' => $value, 'count' => $matches->count()]));
                    }
                },
            ],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'sku' => __('common.fields.sku'),
            'location' => __('common.fields.location'),
            'quantity' => __('common.fields.quantity'),
            'batch_number' => __('purchase_orders.fields.batch_number'),
            'expiry_date' => __('product_batches.fields.expiry_date'),
        ];
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        try {
            DB::transaction(function () use ($data) {
                $batch = ProductBatch::create([
                    'product_id' => $this->resolveProductId((string) $data['sku']),
                    'location_id' => $this->resolveLocationId((string) $data['location']),
                    'batch_number' => filled($data['batch_number'] ?? null) ? trim((string) $data['batch_number']) : null,
                    'quantity' => $data['quantity'],
                    'expiry_date' => filled($data['expiry_date'] ?? null) ? $this->parseDate($data['expiry_date']) : null,
                ]);

                StockMovement::record(
                    batch: $batch,
                    type: StockMovementType::OpeningStock,
                    quantityChange: (float) $data['quantity'],
                );
            });

            $this->importedCount++;
        } catch (Throwable $e) {
            $this->onFailure(new Failure($row->getIndex(), 'quantity', [$e->getMessage()], $data));
        }
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    private function resolveProductId(string $sku): ?int
    {
        $key = trim($sku);

        return $this->productIdBySku[$key] ??= Product::where('sku', $key)->value('id');
    }

    private function resolveLocationId(string $name): ?int
    {
        $matches = $this->locationsByName->get(mb_strtolower(trim($name)));

        return $matches?->first()?->id;
    }

    private function parseDate(mixed $value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse((string) $value);
    }
}
