<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\PurchaseOrderStatus;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.business.pages.stock-levels';

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
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable(),
                TextColumn::make('total_quantity')
                    ->label('On hand')
                    ->sortable(),
                TextColumn::make('on_order_quantity')
                    ->label('On order')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('product.reorder_level')
                    ->label('Reorder at')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (ProductBatch $record) {
                        $onHand = (float) $record->total_quantity;
                        $reorderLevel = $record->product?->reorder_level;

                        return match (true) {
                            $onHand <= 0 => 'Out of stock',
                            $reorderLevel !== null && $onHand <= (float) $reorderLevel => 'Low stock',
                            default => 'OK',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Out of stock' => 'danger',
                        'Low stock' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id')),
            ])
            ->defaultSort('total_quantity', 'asc')
            ->defaultKeySort(false);
    }
}
