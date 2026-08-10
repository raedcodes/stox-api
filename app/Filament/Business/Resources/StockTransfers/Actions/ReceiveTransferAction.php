<?php

namespace App\Filament\Business\Resources\StockTransfers\Actions;

use App\Enums\StockTransferStatus;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use RuntimeException;

/**
 * Mirrors ReceiveStockAction's (Purchase Orders) shape: one repeater line
 * per outstanding item, defaulted to the full remaining quantity but
 * editable down when a delivery arrives short.
 */
class ReceiveTransferAction
{
    public static function make(): Action
    {
        return Action::make('receiveTransfer')
            ->label('Receive')
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('success')
            ->visible(fn (StockTransfer $record) => in_array(
                $record->status,
                [StockTransferStatus::InTransit, StockTransferStatus::PartiallyReceived],
                true,
            ))
            ->schema(fn (StockTransfer $record) => [
                Repeater::make('lines')
                    ->label('Items to receive')
                    ->schema([
                        Hidden::make('stock_transfer_item_id'),
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
                    ])
                    ->default(fn () => $record->items
                        ->filter(fn (StockTransferItem $item) => $item->remainingToReceive() > 0)
                        ->map(fn (StockTransferItem $item) => [
                            'stock_transfer_item_id' => $item->id,
                            'product_name' => $item->product->name,
                            'remaining' => $item->remainingToReceive(),
                            'quantity_received' => $item->remainingToReceive(),
                        ])
                        ->values()
                        ->all())
                    ->columns(4)
                    ->addable(false)
                    ->deletable(false),
            ])
            ->action(function (array $data, StockTransfer $record) {
                $lines = collect($data['lines'] ?? [])
                    ->filter(fn (array $line) => (float) ($line['quantity_received'] ?? 0) > 0);

                if ($lines->isEmpty()) {
                    Notification::make()
                        ->title('Nothing to receive')
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    $record->receive(
                        $lines->mapWithKeys(fn (array $line) => [
                            (int) $line['stock_transfer_item_id'] => (float) $line['quantity_received'],
                        ])->all()
                    );

                    Notification::make()
                        ->title('Transfer received')
                        ->success()
                        ->send();
                } catch (RuntimeException $e) {
                    Notification::make()
                        ->title('Cannot receive transfer')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
