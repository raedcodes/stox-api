<?php

namespace App\Filament\Business\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\SaleItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TopProductsWidget extends TableWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    public function getTableRecordKey(Model|array $record): string
    {
        return (string) $record->product_id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.widgets.top_products_heading'))
            ->query(
                fn (): Builder => SaleItem::query()
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.status', SaleStatus::Completed)
                    ->where('sales.sale_date', '>=', now()->subDays(30))
                    ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) as total_quantity, SUM(sale_items.quantity * sale_items.unit_price) as gross_revenue')
                    ->groupBy('sale_items.product_id')
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('common.fields.product'))
                    ->searchable(),
                TextColumn::make('total_quantity')
                    ->label(__('dashboard.columns.units_sold')),
                TextColumn::make('gross_revenue')
                    ->label(__('dashboard.columns.gross_revenue'))
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->defaultSort('total_quantity', 'desc')
            ->defaultKeySort(false)
            ->paginated([5, 10, 25]);
    }
}
