<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Tables;

use App\Filament\Business\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label('Supplier'),
                TextColumn::make('return_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('total')
                    ->state(fn (PurchaseReturn $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
                IconColumn::make('processed_at')
                    ->label('Processed')
                    ->boolean()
                    ->getStateUsing(fn (PurchaseReturn $record) => $record->processed_at !== null),
            ])
            ->recordUrl(fn (PurchaseReturn $record) => PurchaseReturnResource::getUrl('view', ['record' => $record]))
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('purchase_order_id')
                    ->label('Purchase order')
                    ->options(fn () => PurchaseOrder::query()->pluck('po_number', 'id')),
            ])
            ->recordActions([
                Action::make('process')
                    ->label('Process Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseReturn $record) => $record->processed_at === null)
                    ->action(function (PurchaseReturn $record) {
                        try {
                            $record->process();

                            Notification::make()
                                ->title('Return processed')
                                ->success()
                                ->send();
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title('Cannot process return')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make()
                    ->visible(fn (PurchaseReturn $record) => $record->processed_at === null),
                DeleteAction::make()
                    ->visible(fn (PurchaseReturn $record) => $record->processed_at === null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
