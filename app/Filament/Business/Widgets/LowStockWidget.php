<?php

namespace App\Filament\Business\Widgets;

use App\Enums\UserRole;
use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }


    public function table(Table $table): Table
    {
        // Null means "All Locations" (the CurrentLocationWidget default) —
        // the query stays unscoped in that case.
        $locationId = session('current_location_id');
        $locationClause = $locationId ? ' and product_batches.location_id = ?' : '';

        return $table
            ->heading(__('dashboard.widgets.low_stock_heading'))
            ->query(
                fn (): Builder => Product::query()
                    ->where('is_active', true)
                    ->withSum(['batches as stock_on_hand' => fn ($query) => $query
                        ->where('is_active', true)
                        ->when($locationId, fn ($query, $locationId) => $query->where('location_id', $locationId))], 'quantity')
                    ->whereRaw(
                        "(select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1{$locationClause}) <= 0
                        or (reorder_level is not null and (select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1{$locationClause}) <= reorder_level)",
                        $locationId ? [$locationId, $locationId] : []
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('common.fields.product'))
                    ->searchable(),
                TextColumn::make('sku')
                    ->label(__('common.fields.sku')),
                TextColumn::make('stock_on_hand')
                    ->label(__('dashboard.columns.on_hand'))
                    ->color(fn ($state) => (float) $state <= 0 ? 'danger' : 'warning')
                    ->badge(),
                TextColumn::make('reorder_level')
                    ->label(__('products.fields.reorder_at'))
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('createPurchaseOrder')
                    ->label(__('dashboard.actions.create_po'))
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]) ?? false)
                    ->url(fn (Product $record) => PurchaseOrderResource::getUrl('create').'?product_id='.$record->id),
            ])
            ->defaultSort('stock_on_hand', 'asc')
            ->paginated([5, 10, 25]);
    }
}
