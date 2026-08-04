<?php

namespace App\Filament\Business\Widgets;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Filament\Business\Resources\Sales\SaleResource;
use App\Models\Customer;
use App\Models\Sale;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentCreditSalesWidget extends TableWidget
{
    protected static ?string $heading = 'Recent credit sales';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Sale::query()
                    ->where('status', SaleStatus::Completed)
                    ->where('payment_method', PaymentMethod::Credit)
                    ->with('customer')
            )
            ->columns([
                TextColumn::make('sale_number')
                    ->label('Invoice #'),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('sale_date')
                    ->dateTime(),
                TextColumn::make('total')
                    ->label('Amount')
                    ->state(fn (Sale $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
                TextColumn::make('customer_balance')
                    ->label('Customer owes (total)')
                    ->state(fn (Sale $record) => $record->customer?->balance())
                    ->money(fn () => tenant('currency')?->value)
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
            ])
            ->recordUrl(fn (Sale $record) => SaleResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sale_date', 'desc')
            ->paginated([5, 10, 25]);
    }
}
