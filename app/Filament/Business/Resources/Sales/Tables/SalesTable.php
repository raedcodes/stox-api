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
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('payment_method')
                    ->placeholder('—'),
                TextColumn::make('sale_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('total')
                    ->state(fn (Sale $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('cashier.name')
                    ->label('Cashier')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (Sale $record) => SaleResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sale_date', 'desc')
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options(SaleStatus::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('complete')
                        ->label('Complete Sale')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Draft)
                        ->action(function (Sale $record) {
                            try {
                                $record->complete();

                                Notification::make()
                                    ->title('Sale completed')
                                    ->success()
                                    ->send();
                            } catch (RuntimeException $e) {
                                Notification::make()
                                    ->title('Cannot complete sale')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('printInvoice')
                        ->label('Print Invoice')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Completed)
                        ->url(fn (Sale $record) => route('business.sales.invoice', $record))
                        ->openUrlInNewTab(),
                    Action::make('cancel')
                        ->label('Cancel Sale')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('This restocks everything on the invoice and reverses any charge to the customer\'s account. This cannot be undone.')
                        ->visible(fn (Sale $record) => $record->status === SaleStatus::Completed)
                        ->action(function (Sale $record) {
                            try {
                                $record->cancel();

                                Notification::make()
                                    ->title('Sale cancelled')
                                    ->success()
                                    ->send();
                            } catch (RuntimeException $e) {
                                Notification::make()
                                    ->title('Cannot cancel sale')
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
