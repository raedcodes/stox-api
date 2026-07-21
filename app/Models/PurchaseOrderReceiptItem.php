<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderReceiptItem extends Model
{
    protected $fillable = [
        'purchase_order_receipt_id',
        'purchase_order_item_id',
        'quantity_received',
        'batch_number',
        'expiry_date',
        'product_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceipt::class, 'purchase_order_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }
}
