<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Tables;

use App\Models\Supplier;
use App\SupplierTransactionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupplierTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) => SupplierTransactionType::options()[$state] ?? $state)
                    ->color(fn (string $state) => $state === SupplierTransactionType::Charge ? 'danger' : 'success'),
                TextColumn::make('amount')
                    ->money(fn () => tenant('currency')),
                TextColumn::make('description')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::query()->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->options(SupplierTransactionType::options()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
