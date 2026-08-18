<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Notifications\PurchaseOrderNeedsApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'location_id',
        'po_number',
        'status',
        'order_date',
        'expected_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (blank($order->po_number)) {
                $order->po_number = static::generatePoNumber();
            }
        });
    }

    protected static function generatePoNumber(): string
    {
        $next = (int) (static::query()->max('id')) + 1;

        return 'PO-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(PurchaseOrderCharge::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceipt::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * The order's face value: ordered quantities at their agreed unit cost.
     */
    public function subtotal(): float
    {
        return (float) $this->items()
            ->selectRaw('SUM(quantity_ordered * unit_cost) as total')
            ->value('total');
    }

    /**
     * Additional charges (freight, customs, handling, etc.) — the "landed cost" on top of the order value.
     */
    public function landedCostTotal(): float
    {
        return (float) $this->charges()->sum('amount');
    }

    public function total(): float
    {
        return $this->subtotal() + $this->landedCostTotal();
    }

    public function submitForApproval(): void
    {
        if ($this->status !== PurchaseOrderStatus::Draft) {
            throw new RuntimeException('Only a draft order can be submitted for approval.');
        }

        if ($this->items()->doesntExist()) {
            throw new RuntimeException('Add at least one item before submitting for approval.');
        }

        $this->update(['status' => PurchaseOrderStatus::PendingApproval]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Purchase order {$this->po_number} submitted for approval");

        $this->notifyApprovers();
    }

    /**
     * Emails whoever can approve this order (Owner is a central User,
     * Manager is a tenant-local Staff — see App\Models\Staff for why they
     * can't share a plain foreign key) so approval doesn't sit unnoticed
     * until someone happens to open the Purchase Orders page.
     */
    protected function notifyApprovers(): void
    {
        $owners = User::where('tenant_id', tenant('id'))->get()->filter(fn (User $u) => $u->hasRole(UserRole::Owner));
        $managers = Staff::where('is_active', true)->get()->filter(fn (Staff $s) => $s->hasRole(UserRole::Manager));

        Notification::send($owners->concat($managers), new PurchaseOrderNeedsApproval($this));
    }

    /**
     * Approving IS placing the order with the supplier — there's no
     * separate "place order" step distinct from approval, matching how
     * this system has always treated Ordered as "the order is active."
     */
    public function approve(): void
    {
        if ($this->status !== PurchaseOrderStatus::PendingApproval) {
            throw new RuntimeException('Only an order pending approval can be approved.');
        }

        $this->update(['status' => PurchaseOrderStatus::Ordered]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Purchase order {$this->po_number} approved");
    }

    public function reject(?string $reason = null): void
    {
        if ($this->status !== PurchaseOrderStatus::PendingApproval) {
            throw new RuntimeException('Only an order pending approval can be rejected.');
        }

        $this->update([
            'status' => PurchaseOrderStatus::Rejected,
            'notes' => trim(($this->notes ? $this->notes."\n" : '').'Rejected: '.($reason ?: 'no reason given')),
        ]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Purchase order {$this->po_number} rejected");
    }

    public function cancel(): void
    {
        if (! in_array($this->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::PendingApproval, PurchaseOrderStatus::Ordered], true)) {
            throw new RuntimeException('An order that has already received stock, or is already rejected/cancelled, cannot be cancelled.');
        }

        $this->update(['status' => PurchaseOrderStatus::Cancelled]);

        activity()->causedBy(auth()->user())->performedOn($this)
            ->log("Purchase order {$this->po_number} cancelled");
    }

    /**
     * Recompute status from each item's received-vs-ordered quantity. Only
     * touches Ordered/Partially Received/Received — never overrides Draft,
     * PendingApproval, Rejected, or Cancelled, which are only ever set by
     * the explicit workflow actions above.
     */
    public function recomputeStatus(): void
    {
        if (in_array($this->status, [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::PendingApproval,
            PurchaseOrderStatus::Rejected,
            PurchaseOrderStatus::Cancelled,
        ], true)) {
            return;
        }

        $items = $this->items()->get(['quantity_ordered', 'quantity_received']);

        $totalReceived = $items->sum('quantity_received');

        $status = match (true) {
            $totalReceived <= 0 => PurchaseOrderStatus::Ordered,
            $items->every(fn (PurchaseOrderItem $item) => $item->quantity_received >= $item->quantity_ordered) => PurchaseOrderStatus::Received,
            default => PurchaseOrderStatus::PartiallyReceived,
        };

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
