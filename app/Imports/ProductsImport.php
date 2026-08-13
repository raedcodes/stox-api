<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Deliberately NOT combined with WithBatchInserts: Product::booted() has a
 * `creating` hook (auto-generateSku() when blank) and a `created` hook
 * (auto-creates the default ProductVariant) that only fire on a real
 * single-row save()/create() call. Without WithBatchInserts, Maatwebsite
 * Excel calls saveOrFail() once per row individually, preserving both.
 * WithChunkReading only paginates *reading* the file into memory and is
 * safe alongside this.
 */
class ProductsImport implements SkipsOnError, SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    use Importable, SkipsErrors, SkipsFailures;

    /**
     * Lowercased+trimmed SKU/barcode already seen in this file, populated
     * in model() right after that row's own validation passed. Checked by
     * a rules() closure on every subsequent row so in-file duplicates are
     * caught deterministically, independent of DB unique-check timing.
     *
     * @var array<string, true>
     */
    private array $seenSkus = [];

    /**
     * @var array<string, true>
     */
    private array $seenBarcodes = [];

    /**
     * @var array<string, int>
     */
    private array $categoryCache = [];

    /**
     * @var array<string, int>
     */
    private array $brandCache = [];

    /**
     * @var array<string, int>
     */
    private array $unitCache = [];

    /**
     * Comma-separated "Name (abbreviation)" list, built once for the
     * unit_not_found error message so a mismatch is self-diagnosable
     * instead of leaving the user to guess what a valid value looks like.
     */
    private string $availableUnitsList = '';

    private int $importedCount = 0;

    public function __construct()
    {
        $units = UnitOfMeasure::all();

        foreach ($units as $unit) {
            $this->unitCache[mb_strtolower($unit->name)] = $unit->id;
            $this->unitCache[mb_strtolower($unit->abbreviation)] = $unit->id;
        }

        $this->availableUnitsList = $units->map(fn (UnitOfMeasure $unit) => "{$unit->name} ({$unit->abbreviation})")->implode(', ');
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                // Deliberately no 'string' rule: Excel/CSV often stores a
                // numeric-looking SKU or barcode as a number, not text, so
                // PHP's is_string() check would wrongly reject an otherwise
                // valid row. Every downstream use already casts via (string).
                'nullable', 'max:255',
                Rule::unique('products', 'sku'),
                fn ($attribute, $value, $fail) => $this->failIfDuplicateInFile($value, $this->seenSkus, $fail, 'products.import.duplicate_sku_in_file'),
            ],
            'barcode' => [
                'nullable', 'max:255',
                Rule::unique('products', 'barcode'),
                fn ($attribute, $value, $fail) => $this->failIfDuplicateInFile($value, $this->seenBarcodes, $fail, 'products.import.duplicate_barcode_in_file'),
            ],
            'unit' => [
                'required', 'string',
                fn ($attribute, $value, $fail) => $this->resolveUnitId($value) === null
                    && $fail(__('products.import.unit_not_found', ['unit' => $value, 'available' => $this->availableUnitsList])),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'wsp' => ['nullable', 'numeric', 'min:0'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'name' => __('common.fields.name'),
            'sku' => __('common.fields.sku'),
            'barcode' => __('common.fields.barcode'),
            'unit' => __('products.fields.unit'),
            'category' => __('common.fields.category'),
            'brand' => __('common.fields.brand'),
            'cost_price' => __('products.fields.cost_price'),
            'selling_price' => __('products.fields.selling_price'),
            'wsp' => __('products.fields.wsp'),
            'min_stock_level' => __('products.fields.min_stock_level'),
            'reorder_level' => __('products.fields.reorder_level'),
            'active' => __('common.fields.is_active'),
            'description' => __('common.fields.description'),
        ];
    }

    public function model(array $row): Product
    {
        $sku = filled($row['sku'] ?? null) ? trim((string) $row['sku']) : null;
        $barcode = filled($row['barcode'] ?? null) ? trim((string) $row['barcode']) : null;

        if ($sku !== null) {
            $this->seenSkus[mb_strtolower($sku)] = true;
        }

        if ($barcode !== null) {
            $this->seenBarcodes[mb_strtolower($barcode)] = true;
        }

        $this->importedCount++;

        return new Product([
            'name' => trim((string) $row['name']),
            'sku' => $sku,
            'barcode' => $barcode,
            'category_id' => $this->resolveCategoryId($row['category'] ?? null),
            'brand_id' => $this->resolveBrandId($row['brand'] ?? null),
            'unit_of_measure_id' => $this->resolveUnitId($row['unit']),
            'cost_price' => $row['cost_price'] ?? null,
            'selling_price' => $row['selling_price'],
            'wsp' => $row['wsp'] ?? null,
            'min_stock_level' => $row['min_stock_level'] ?? null,
            'reorder_level' => $row['reorder_level'] ?? null,
            'is_active' => $this->parseBool($row['active'] ?? null),
            'description' => filled($row['description'] ?? null) ? trim((string) $row['description']) : null,
        ]);
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    private function resolveCategoryId(?string $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        $key = mb_strtolower(trim($name));

        return $this->categoryCache[$key] ??= Category::firstOrCreate(['name' => trim($name)])->id;
    }

    private function resolveBrandId(?string $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        $key = mb_strtolower(trim($name));

        return $this->brandCache[$key] ??= Brand::firstOrCreate(['name' => trim($name)])->id;
    }

    private function resolveUnitId(string $name): ?int
    {
        return $this->unitCache[mb_strtolower(trim($name))] ?? null;
    }

    /**
     * @param  array<string, true>  $seen
     */
    private function failIfDuplicateInFile(?string $value, array $seen, \Closure $fail, string $langKey): void
    {
        if (filled($value) && isset($seen[mb_strtolower(trim($value))])) {
            $fail(__($langKey, ['value' => $value]));
        }
    }

    private function parseBool(mixed $value): bool
    {
        if (blank($value)) {
            return true;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'yes', 'true', 'y'], true);
    }
}
