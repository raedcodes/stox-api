<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Actions;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Enums\SupplierTransactionType;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\DB;

class ReceiveStockAction
{
    public static function make(): Action
    {
        return Action::make('receive')
            ->label(__('purchase_orders.actions.receive_stock'))
            ->icon('heroicon-o-truck')
            ->visible(fn (PurchaseOrder $record) => in_array($record->status, [
                PurchaseOrderStatus::Ordered,
                PurchaseOrderStatus::PartiallyReceived,
            ], true))
            ->schema(fn (PurchaseOrder $record) => [
                DatePicker::make('received_date')
                    ->label(__('common.fields.date'))
                    ->required()
                    ->default(now()),
                Repeater::make('lines')
                    ->label(__('purchase_orders.fields.items_to_receive'))
                    ->schema([
                        Hidden::make('purchase_order_item_id'),
                        TextInput::make('product_name')
                            ->label(__('common.fields.product'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('remaining')
                            ->label(__('purchase_orders.fields.remaining'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('quantity_received')
                            ->label(__('purchase_orders.fields.receive_now'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => (float) $get('remaining'))
                            ->required(),
                        TextInput::make('batch_number')
                            ->label(__('purchase_orders.fields.batch_number')),
                        DatePicker::make('expiry_date')
                            ->label(__('product_batches.fields.expiry_date')),
                    ])
                    ->default(fn () => $record->items
                        ->filter(fn (PurchaseOrderItem $item) => $item->remainingQuantity() > 0)
                        ->map(fn (PurchaseOrderItem $item) => [
                            'purchase_order_item_id' => $item->id,
                            'product_name' => $item->product->name,
                            'remaining' => $item->remainingQuantity(),
                            'quantity_received' => $item->remainingQuantity(),
                            'batch_number' => null,
                            'expiry_date' => null,
                        ])
                        ->values()
                        ->all())
                    ->columns(5)
                    ->addable(false)
                    ->deletable(false),
            ])
            ->action(function (array $data, PurchaseOrder $record) {
                $lines = collect($data['lines'] ?? [])
                    ->filter(fn (array $line) => (float) ($line['quantity_received'] ?? 0) > 0);

                if ($lines->isEmpty()) {
                    Notification::make()
                        ->title(__('purchase_orders.notifications.nothing_to_receive'))
                        ->warning()
                        ->send();

                    return;
                }

                DB::transaction(function () use ($data, $record, $lines) {
                    $receipt = $record->receipts()->create([
                        'received_date' => $data['received_date'],
                    ]);

                    $poItems = PurchaseOrderItem::findMany($lines->pluck('purchase_order_item_id'))->keyBy('id');

                    // Landed costs (freight, customs, handling — entered once for the
                    // whole order) get allocated proportionally by value across whatever
                    // is being received right now, so a partial receipt only carries its
                    // fair share and the total converges exactly once fully received.
                    $poSubtotal = $record->subtotal();
                    $poLandedCostTotal = $record->landedCostTotal();

                    $receiptRawValue = $lines->sum(
                        fn (array $line) => (float) $line['quantity_received'] * (float) $poItems[$line['purchase_order_item_id']]->unit_cost
                    );

                    $receiptAllocatedLandedCost = $poSubtotal > 0
                        ? ($receiptRawValue / $poSubtotal) * $poLandedCostTotal
                        : 0;

                    $totalValue = 0;

                    foreach ($lines as $line) {
                        $poItem = $poItems[$line['purchase_order_item_id']];
                        $quantity = (float) $line['quantity_received'];
                        $lineRawValue = $quantity * (float) $poItem->unit_cost;

                        $lineAllocatedLandedCost = $receiptRawValue > 0
                            ? ($lineRawValue / $receiptRawValue) * $receiptAllocatedLandedCost
                            : 0;

                        // The batch's real cost basis: what was paid per unit, plus its
                        // share of landed costs — not just the PO's raw unit_cost.
                        $landedUnitCost = (float) $poItem->unit_cost + ($lineAllocatedLandedCost / $quantity);

                        // Always tracked as a batch, even without a real batch number/expiry —
                        // this is the only record of on-hand stock, so every receipt needs one
                        // for sales to have anything to deduct from later.
                        $productBatch = ProductBatch::create([
                            'product_id' => $poItem->product_id,
                            'product_variant_id' => $poItem->product_variant_id,
                            'location_id' => $record->location_id,
                            'batch_number' => $line['batch_number'] ?? null,
                            'quantity' => $quantity,
                            'unit_cost' => $landedUnitCost,
                            'expiry_date' => $line['expiry_date'] ?? null,
                        ]);

                        $receipt->items()->create([
                            'purchase_order_item_id' => $poItem->id,
                            'quantity_received' => $quantity,
                            'batch_number' => $line['batch_number'] ?? null,
                            'expiry_date' => $line['expiry_date'] ?? null,
                            'product_batch_id' => $productBatch?->id,
                        ]);

                        StockMovement::record(
                            batch: $productBatch,
                            type: StockMovementType::PurchaseReceipt,
                            quantityChange: $quantity,
                            reference: $record,
                        );

                        $poItem->increment('quantity_received', $quantity);

                        $totalValue += $lineRawValue + $lineAllocatedLandedCost;
                    }

                    if ($totalValue > 0) {
                        $record->supplier->transactions()->create([
                            'type' => SupplierTransactionType::Charge,
                            'amount' => $totalValue,
                            'description' => $receiptAllocatedLandedCost > 0
                                ? "Receipt against {$record->po_number} (incl. landed costs)"
                                : "Receipt against {$record->po_number}",
                            'transaction_date' => $data['received_date'],
                            'reference_type' => $record->getMorphClass(),
                            'reference_id' => $record->id,
                        ]);
                    }

                    $record->recomputeStatus();

                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($record)
                        ->withProperties(['po_number' => $record->po_number, 'total_value' => $totalValue])
                        ->log("Stock received against PO {$record->po_number}");
                });

                Notification::make()
                    ->title(__('purchase_orders.notifications.received'))
                    ->success()
                    ->send();
            });
    }
}
