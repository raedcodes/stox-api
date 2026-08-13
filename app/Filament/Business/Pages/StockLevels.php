<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StockLevels extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.business.pages.stock-levels';

    public static function getNavigationLabel(): string
    {
        return __('stock_levels.navigation_label');
    }

    public function getTitle(): string
    {
        return __('stock_levels.navigation_label');
    }

    public function getTableRecordKey(Model|array $record): string
    {
        return "{$record->product_id}-{$record->location_id}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductBatch::query()
                    ->selectRaw(
                        'product_id, location_id, SUM(quantity) as total_quantity, ('
                        .'select coalesce(sum(poi.quantity_ordered - poi.quantity_received), 0) '
                        .'from purchase_order_items poi '
                        .'inner join purchase_orders po on po.id = poi.purchase_order_id '
                        .'where poi.product_id = product_batches.product_id '
                        .'and po.location_id = product_batches.location_id '
                        .'and po.status in (?, ?)'
                        .') as on_order_quantity',
                        [PurchaseOrderStatus::Ordered->value, PurchaseOrderStatus::PartiallyReceived->value],
                    )
                    ->where('is_active', true)
                    ->groupBy('product_id', 'location_id')
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('common.fields.product'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.sku')
                    ->label(__('common.fields.sku'))
                    ->searchable(),
                TextColumn::make('product.brand.name')
                    ->label(__('common.fields.brand'))
                    ->placeholder('—'),
                TextColumn::make('location.name')
                    ->label(__('common.fields.location'))
                    ->sortable(),
                TextColumn::make('total_quantity')
                    ->label(__('dashboard.columns.on_hand'))
                    ->sortable(),
                TextColumn::make('on_order_quantity')
                    ->label(__('stock_levels.fields.on_order'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('product.reorder_level')
                    ->label(__('products.fields.reorder_at'))
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->state(function (ProductBatch $record) {
                        $onHand = (float) $record->total_quantity;
                        $reorderLevel = $record->product?->reorder_level;

                        return match (true) {
                            $onHand <= 0 => __('stock_levels.status.out_of_stock'),
                            $reorderLevel !== null && $onHand <= (float) $reorderLevel => __('stock_levels.status.low_stock'),
                            default => __('stock_levels.status.ok'),
                        };
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        __('stock_levels.status.out_of_stock') => 'danger',
                        __('stock_levels.status.low_stock') => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label(__('common.fields.product'))
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
            ])
            ->defaultSort('total_quantity', 'asc')
            ->defaultKeySort(false);
    }
}
