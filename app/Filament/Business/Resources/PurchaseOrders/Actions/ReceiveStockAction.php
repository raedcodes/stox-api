<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Actions;

use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\PurchaseOrderStatus;
use App\SupplierTransactionType;
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
            ->label('Receive Stock')
            ->icon('heroicon-o-truck')
            ->visible(fn (PurchaseOrder $record) => in_array($record->status, [
                PurchaseOrderStatus::Ordered,
                PurchaseOrderStatus::PartiallyReceived,
            ], true))
            ->schema(fn (PurchaseOrder $record) => [
                DatePicker::make('received_date')
                    ->required()
                    ->default(now()),
                Repeater::make('lines')
                    ->label('Items to receive')
                    ->schema([
                        Hidden::make('purchase_order_item_id'),
                        TextInput::make('product_name')
                            ->label('Product')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('remaining')
                            ->label('Remaining')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('quantity_received')
                            ->label('Receive now')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => (float) $get('remaining'))
                            ->required(),
                        TextInput::make('batch_number')
                            ->label('Batch #'),
                        DatePicker::make('expiry_date'),
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
                        ->title('Nothing to receive')
                        ->warning()
                        ->send();

                    return;
                }

                DB::transaction(function () use ($data, $record, $lines) {
                    $receipt = $record->receipts()->create([
                        'received_date' => $data['received_date'],
                    ]);

                    $totalValue = 0;

                    foreach ($lines as $line) {
                        $poItem = PurchaseOrderItem::findOrFail($line['purchase_order_item_id']);
                        $quantity = (float) $line['quantity_received'];

                        $productBatch = null;

                        if (filled($line['batch_number'] ?? null)) {
                            $productBatch = ProductBatch::create([
                                'product_id' => $poItem->product_id,
                                'location_id' => $record->location_id,
                                'batch_number' => $line['batch_number'],
                                'quantity' => $quantity,
                                'expiry_date' => $line['expiry_date'] ?? null,
                            ]);
                        }

                        $receipt->items()->create([
                            'purchase_order_item_id' => $poItem->id,
                            'quantity_received' => $quantity,
                            'batch_number' => $line['batch_number'] ?? null,
                            'expiry_date' => $line['expiry_date'] ?? null,
                            'product_batch_id' => $productBatch?->id,
                        ]);

                        $poItem->increment('quantity_received', $quantity);

                        $totalValue += $quantity * (float) $poItem->unit_cost;
                    }

                    if ($totalValue > 0) {
                        $record->supplier->transactions()->create([
                            'type' => SupplierTransactionType::Charge,
                            'amount' => $totalValue,
                            'description' => "Receipt against {$record->po_number}",
                            'transaction_date' => $data['received_date'],
                        ]);
                    }

                    $record->recomputeStatus();
                });

                Notification::make()
                    ->title('Stock received')
                    ->success()
                    ->send();
            });
    }
}
