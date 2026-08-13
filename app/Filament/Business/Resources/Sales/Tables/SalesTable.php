<?php

namespace App\Filament\Business\Resources\Sales\Tables;

use App\Enums\SaleStatus;
use App\Filament\Business\Resources\Sales\SaleResource;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'location', 'cashier', 'items.product']))
            ->columns([
                TextColumn::make('sale_number')
                    ->label(__('common.fields.invoice_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('common.fields.customer'))
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label(__('common.fields.location')),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->badge(),
                TextColumn::make('payment_method')
                    ->label(__('common.fields.payment_method'))
                    ->placeholder('—'),
                TextColumn::make('sale_date')
                    ->label(__('common.fields.date'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('total')
                    ->label(__('common.fields.total'))
                    ->state(fn (Sale $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('cashier.name')
                    ->label(__('common.fields.cashier'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (Sale $record) => SaleResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sale_date', 'desc')
            ->filters([
                SelectFilter::make('customer_id')
                    ->label(__('common.fields.customer'))
                    ->options(fn () => Customer::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label(__('common.fields.status'))
                    ->options(SaleStatus::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('complete')
                        ->label(__('sales.actions.complete_sale'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Draft)
                        ->action(function (Sale $record) {
                            try {
                                $record->complete();

                                Notification::make()
                                    ->title(__('sales.notifications.completed'))
                                    ->success()
                                    ->send();
                            } catch (RuntimeException $e) {
                                Notification::make()
                                    ->title(__('sales.notifications.cannot_complete'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('printInvoice')
                        ->label(__('sales.actions.print_invoice'))
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Completed)
                        ->url(fn (Sale $record) => route('business.sales.invoice', $record))
                        ->openUrlInNewTab(),
                    Action::make('cancel')
                        ->label(__('sales.actions.cancel_sale'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription(__('sales.cancel_sale_description'))
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Completed)
                        ->action(function (Sale $record) {
                            try {
                                $record->cancel();

                                Notification::make()
                                    ->title(__('sales.notifications.cancelled'))
                                    ->success()
                                    ->send();
                            } catch (RuntimeException $e) {
                                Notification::make()
                                    ->title(__('sales.notifications.cannot_cancel'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    EditAction::make()
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Draft),
                    DeleteAction::make()
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Draft),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
