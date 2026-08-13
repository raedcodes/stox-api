<?php

namespace App\Filament\Business\Resources\CustomerTransactions\Tables;

use App\Enums\CustomerTransactionType;
use App\Filament\Business\Resources\CustomerTransactions\CustomerTransactionResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use Filament\Actions\ActionGroup;
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
                TextColumn::make('transaction_number')
                    ->label(__('common.fields.transaction_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label(__('common.fields.customer'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('common.fields.type')),
                TextColumn::make('amount')
                    ->label(__('common.fields.amount'))
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('referenceLabel')
                    ->label(__('common.fields.reference'))
                    ->state(fn (CustomerTransaction $record) => $record->referenceLabel())
                    ->placeholder('—'),
                TextColumn::make('description')
                    ->label(__('common.fields.description'))
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('transaction_date')
                    ->label(__('common.fields.date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(fn (CustomerTransaction $record) => CustomerTransactionResource::getUrl('view', ['record' => $record]))
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('customer_id')
                    ->label(__('common.fields.customer'))
                    ->options(fn () => Customer::query()->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->label(__('common.fields.type'))
                    ->options(CustomerTransactionType::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
