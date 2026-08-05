<?php

namespace App\Filament\Business\Widgets;

use App\Enums\CustomerTransactionType;
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentCreditSalesWidget extends TableWidget
{
    protected static ?string $heading = 'Recent credit sales';

    protected int|string|array $columnSpan = 'full';

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
                    // items.product: ->total() reads $this->items/->product.
                    // The two withSums on customer replace calling
                    // ->balance() per row (its own aggregate query every
                    // time) with two correlated subqueries computed once,
                    // for the whole page, by the database.
                    ->with(['items.product', 'customer' => fn (BelongsTo $query) => $query
                        ->withSum(['transactions as credit_sales_sum' => fn (Builder $query) => $query->where('type', CustomerTransactionType::CreditSale)], 'amount')
                        ->withSum(['transactions as reducing_sum' => fn (Builder $query) => $query->whereIn('type', array_map(fn (CustomerTransactionType $type) => $type->value, CustomerTransactionType::reducingTypes()))], 'amount')])
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
                    ->state(fn (Sale $record) => $record->customer
                        ? (float) ($record->customer->credit_sales_sum ?? 0) - (float) ($record->customer->reducing_sum ?? 0)
                        : null)
                    ->money(fn () => tenant('currency')?->value)
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
            ])
            ->recordUrl(fn (Sale $record) => SaleResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sale_date', 'desc')
            ->paginated([5, 10, 25]);
    }
}
