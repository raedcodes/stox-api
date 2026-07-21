<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Tables;

use App\CustomerTransactionType;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state) => CustomerTransactionType::options()[$state] ?? $state)
                    ->color(fn (string $state) => $state === CustomerTransactionType::CreditSale ? 'danger' : 'success'),
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
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::query()->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->options(CustomerTransactionType::options()),
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
