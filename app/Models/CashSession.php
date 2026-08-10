<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class CashSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'location_id',
        'status',
        'opening_float',
        'opened_at',
        'opened_by',
        'opener_type',
        'expected_cash',
        'counted_cash',
        'variance',
        'closed_at',
        'closed_by',
        'closer_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CashSessionStatus::class,
            'opening_float' => 'decimal:2',
            'opened_at' => 'datetime',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'variance' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function opener(): MorphTo
    {
        return $this->morphTo(name: 'opener', type: 'opener_type', id: 'opened_by');
    }

    public function closer(): MorphTo
    {
        return $this->morphTo(name: 'closer', type: 'closer_type', id: 'closed_by');
    }

    public static function openAt(int $locationId, float $openingFloat): self
    {
        if (static::query()->where('location_id', $locationId)->where('status', CashSessionStatus::Open)->exists()) {
            throw new RuntimeException('A till is already open for this location.');
        }

        return static::create([
            'location_id' => $locationId,
            'status' => CashSessionStatus::Open,
            'opening_float' => $openingFloat,
            'opened_at' => now(),
            'opened_by' => auth()->user()?->getKey(),
            'opener_type' => auth()->user()?->getMorphClass(),
        ]);
    }

    /**
     * Cash that should physically be in the drawer right now: the opening
     * float, plus completed cash sales rung up since opening, minus cash
     * refunded on processed returns against cash sales — all scoped to this
     * session's location and time window.
     */
    public function expectedCash(): float
    {
        return $this->computeExpectedCash($this->closed_at ?? now());
    }

    protected function computeExpectedCash(\DateTimeInterface $until): float
    {
        $cashSales = (float) Sale::query()
            ->where('location_id', $this->location_id)
            ->where('status', SaleStatus::Completed)
            ->where('payment_method', PaymentMethod::Cash)
            ->whereBetween('sale_date', [$this->opened_at, $until])
            ->get()
            ->sum(fn (Sale $sale) => $sale->total());

        $cashRefunds = (float) SaleReturn::query()
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [$this->opened_at, $until])
            ->whereHas('sale', fn ($query) => $query
                ->where('location_id', $this->location_id)
                ->where('payment_method', PaymentMethod::Cash))
            ->get()
            ->sum(fn (SaleReturn $return) => $return->total());

        return (float) $this->opening_float + $cashSales - $cashRefunds;
    }

    public function close(float $countedCash, ?string $notes = null): void
    {
        if ($this->status !== CashSessionStatus::Open) {
            throw new RuntimeException('Only an open till can be closed.');
        }

        $closedAt = now();
        $expected = $this->computeExpectedCash($closedAt);

        $this->update([
            'status' => CashSessionStatus::Closed,
            'closed_at' => $closedAt,
            'closed_by' => auth()->user()?->getKey(),
            'closer_type' => auth()->user()?->getMorphClass(),
            'expected_cash' => $expected,
            'counted_cash' => $countedCash,
            'variance' => $countedCash - $expected,
            'notes' => $notes,
        ]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Till closed for {$this->location->name} (variance: ".number_format($this->variance, 2).')');
    }
}
