<?php

namespace App\Filament\Business\Resources\PurchaseReturns\Tables;

use App\Filament\Business\Resources\PurchaseReturns\PurchaseReturnResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['purchaseOrder.supplier'])
                // Precomputed instead of calling ->total() per row: that
                // method always issues its own aggregate query regardless of
                // eager loading, since it queries the relation builder
                // directly rather than a loaded collection.
                ->selectRaw('purchase_returns.*, (
                    select coalesce(sum(quantity * unit_cost), 0) from purchase_return_items where purchase_return_items.purchase_return_id = purchase_returns.id
                ) as computed_total'))
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label(__('common.fields.po_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label(__('common.fields.supplier')),
                TextColumn::make('return_date')
                    ->label(__('common.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label(__('common.fields.reason'))
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('total')
                    ->label(__('common.fields.total'))
                    ->state(fn (PurchaseReturn $record) => $record->computed_total)
                    ->money(fn () => tenant('currency')?->value),
                IconColumn::make('processed_at')
                    ->label(__('common.fields.processed'))
                    ->boolean()
                    ->getStateUsing(fn (PurchaseReturn $record) => $record->processed_at !== null),
            ])
            ->recordUrl(fn (PurchaseReturn $record) => PurchaseReturnResource::getUrl('view', ['record' => $record]))
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('purchase_order_id')
                    ->label(__('purchase_returns.fields.purchase_order'))
                    ->options(fn () => PurchaseOrder::query()->pluck('po_number', 'id')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('process')
                        ->label(__('common.actions.process_return'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (PurchaseReturn $record) => $record->processed_at === null)
                        ->action(function (PurchaseReturn $record) {
                            try {
                                $record->process();

                                Notification::make()
                                    ->title(__('common.notifications.return_processed'))
                                    ->success()
                                    ->send();
                            } catch (RuntimeException $e) {
                                Notification::make()
                                    ->title(__('common.notifications.cannot_process_return'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    EditAction::make()
                        ->visible(fn (PurchaseReturn $record) => $record->processed_at === null),
                    DeleteAction::make()
                        ->visible(fn (PurchaseReturn $record) => $record->processed_at === null),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
