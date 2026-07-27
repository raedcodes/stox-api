<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Tables;

use App\Enums\CustomerTransactionType;
use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
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
                TextColumn::make('type'),
                TextColumn::make('amount')
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('description')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('transaction_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(fn (CustomerTransaction $record) => CustomerTransactionResource::getUrl('view', ['record' => $record]))
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
