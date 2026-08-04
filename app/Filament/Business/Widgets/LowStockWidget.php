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

    protected static ?string $heading = 'Low & out-of-stock products';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Product::query()
                    ->where('is_active', true)
                    ->withSum(['batches as stock_on_hand' => fn ($query) => $query->where('is_active', true)], 'quantity')
                    ->whereRaw(
                        '(select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1) <= 0
                        or (reorder_level is not null and (select coalesce(sum(quantity), 0) from product_batches where product_batches.product_id = products.id and product_batches.is_active = 1) <= reorder_level)'
                    )
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU'),
                TextColumn::make('stock_on_hand')
                    ->label('On hand')
                    ->color(fn ($state) => (float) $state <= 0 ? 'danger' : 'warning')
                    ->badge(),
                TextColumn::make('reorder_level')
                    ->label('Reorder at')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('createPurchaseOrder')
                    ->label('Create PO')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]) ?? false)
                    ->url(fn (Product $record) => PurchaseOrderResource::getUrl('create').'?product_id='.$record->id),
            ])
            ->defaultSort('stock_on_hand', 'asc')
            ->paginated([5, 10, 25]);
    }
}
