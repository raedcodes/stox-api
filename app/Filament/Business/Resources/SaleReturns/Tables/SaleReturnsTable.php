<?php

namespace App\Filament\Business\Resources\SaleReturns\Tables;

use App\Models\Sale;
use App\Models\SaleReturn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

class SaleReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale.sale_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sale.customer.name')
                    ->label('Customer'),
                TextColumn::make('return_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('total')
                    ->state(fn (SaleReturn $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
                IconColumn::make('processed_at')
                    ->label('Processed')
                    ->boolean()
                    ->getStateUsing(fn (SaleReturn $record) => $record->processed_at !== null),
            ])
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('sale_id')
                    ->label('Invoice')
                    ->options(fn () => Sale::query()->pluck('sale_number', 'id')),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('process')
                    ->label('Process Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SaleReturn $record) => $record->processed_at === null)
                    ->action(function (SaleReturn $record) {
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
                    ->visible(fn (SaleReturn $record) => $record->processed_at === null),
                DeleteAction::make()
                    ->visible(fn (SaleReturn $record) => $record->processed_at === null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
