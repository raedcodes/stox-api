<?php

namespace App\Filament\Business\Resources\SaleReturns\Tables;

use App\Filament\Business\Resources\SaleReturns\SaleReturnResource;
use App\Models\Sale;
use App\Models\SaleReturn;
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

class SaleReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['sale.customer'])
                // Precomputed instead of calling ->total() per row: that
                // method always issues its own aggregate query regardless of
                // eager loading, since it queries the relation builder
                // directly rather than a loaded collection.
                ->selectRaw('sale_returns.*, (
                    select coalesce(sum(quantity * unit_price), 0) from sale_return_items where sale_return_items.sale_return_id = sale_returns.id
                ) as computed_total'))
            ->columns([
                TextColumn::make('sale.sale_number')
                    ->label(__('common.fields.invoice_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sale.customer.name')
                    ->label(__('common.fields.customer')),
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
                    ->state(fn (SaleReturn $record) => $record->computed_total)
                    ->money(fn () => tenant('currency')?->value),
                IconColumn::make('processed_at')
                    ->label(__('common.fields.processed'))
                    ->boolean()
                    ->getStateUsing(fn (SaleReturn $record) => $record->processed_at !== null),
            ])
            ->recordUrl(fn (SaleReturn $record) => SaleReturnResource::getUrl('view', ['record' => $record]))
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('sale_id')
                    ->label(__('sale_returns.fields.invoice'))
                    ->options(fn () => Sale::query()->pluck('sale_number', 'id')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('process')
                        ->label(__('common.actions.process_return'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (SaleReturn $record) => $record->processed_at === null)
                        ->action(function (SaleReturn $record) {
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
                        ->visible(fn (SaleReturn $record) => $record->processed_at === null),
                    DeleteAction::make()
                        ->visible(fn (SaleReturn $record) => $record->processed_at === null),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
