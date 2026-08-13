<?php

namespace App\Filament\Business\Resources\SupplierTransactions\Tables;

use App\Enums\SupplierTransactionType;
use App\Filament\Business\Resources\SupplierTransactions\SupplierTransactionResource;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Filament\Actions\ActionGroup;
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
                TextColumn::make('transaction_number')
                    ->label(__('common.fields.transaction_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label(__('common.fields.supplier'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('common.fields.type')),
                TextColumn::make('amount')
                    ->label(__('common.fields.amount'))
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('referenceLabel')
                    ->label(__('common.fields.reference'))
                    ->state(fn (SupplierTransaction $record) => $record->referenceLabel())
                    ->placeholder('—'),
                TextColumn::make('description')
                    ->label(__('common.fields.description'))
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('transaction_date')
                    ->label(__('common.fields.date'))
                    ->date()
                    ->sortable(),
            ])
            ->recordUrl(fn (SupplierTransaction $record) => SupplierTransactionResource::getUrl('view', ['record' => $record]))
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label(__('common.fields.supplier'))
                    ->options(fn () => Supplier::query()->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->label(__('common.fields.type'))
                    ->options(SupplierTransactionType::options()),
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
