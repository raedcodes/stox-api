<?php

namespace App\Filament\Business\Resources\Products\Tables;

use App\Filament\Business\Resources\Products\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UnitOfMeasure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum(['batches as stock_on_hand' => fn ($query) => $query->where('is_active', true)], 'quantity')
                ->withCount(['variants as active_variants_count' => fn ($query) => $query->where('is_active', true)]))
            ->columns([
                ImageColumn::make('images')
                    ->label('')
                    ->limit(1)
                    ->circular(false),
                TextColumn::make('name')
                    ->label(__('common.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label(__('common.fields.sku'))
                    ->searchable(),
                TextColumn::make('stock_on_hand')
                    ->label(__('products.fields.total_stock'))
                    ->numeric()
                    ->placeholder('0')
                    ->sortable(),
                TextColumn::make('active_variants_count')
                    ->label(__('products.fields.variants'))
                    ->badge()
                    ->formatStateUsing(fn (int $state) => $state > 1 ? __('products.variants_count', ['count' => $state]) : __('products.single'))
                    ->color(fn (int $state) => $state > 1 ? 'info' : 'gray'),
                TextColumn::make('description')
                    ->label(__('common.fields.description'))
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('barcode')
                    ->label(__('common.fields.barcode'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label(__('common.fields.category'))
                    ->placeholder('—'),
                TextColumn::make('brand.name')
                    ->label(__('common.fields.brand'))
                    ->placeholder('—'),
                TextColumn::make('unit.abbreviation')
                    ->label(__('products.fields.unit')),
                TextColumn::make('min_stock_level')
                    ->label(__('products.fields.min_stock'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reorder_level')
                    ->label(__('products.fields.reorder_at'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_price')
                    ->label(__('products.fields.cost_price'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('selling_price')
                    ->label(__('products.fields.selling_price'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—'),
                TextColumn::make('wsp')
                    ->label(__('products.fields.wsp_short'))
                    ->money(fn () => tenant('currency')?->value)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cbm')
                    ->label(__('products.fields.cbm_short'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('tax_inclusive')
                    ->label(__('products.fields.tax_incl'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at_since')
                    ->label(__('common.fields.since'))
                    ->state(fn (Product $record) => $record->created_at)
                    ->since()
                    ->dateTooltip()
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('created_at', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('common.fields.category'))
                    ->options(fn () => Category::query()->pluck('name', 'id')),
                SelectFilter::make('brand_id')
                    ->label(__('common.fields.brand'))
                    ->options(fn () => Brand::query()->pluck('name', 'id')),
                SelectFilter::make('unit_of_measure_id')
                    ->label(__('products.fields.unit'))
                    ->options(fn () => UnitOfMeasure::query()->pluck('name', 'id')),
                TernaryFilter::make('is_active')
                    ->label(__('common.fields.is_active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('viewVariants')
                    ->label(__('products.actions.view_variants'))
                    ->icon('heroicon-o-squares-2x2')
                    ->color('gray')
                    ->visible(fn (Product $record) => $record->active_variants_count > 1)
                    ->slideOver()
                    ->modalHeading(fn (Product $record) => __('products.variants_modal_heading', ['name' => $record->name]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('products.actions.close'))
                    ->schema(fn (Product $record) => [
                        RepeatableEntry::make('variants')
                            ->label('')
                            ->state(fn () => $record->variants()->orderByDesc('is_default')->get())
                            ->table([
                                TableColumn::make(__('products.fields.size')),
                                TableColumn::make(__('products.fields.color')),
                                TableColumn::make(__('common.fields.sku')),
                                TableColumn::make(__('common.fields.barcode')),
                                TableColumn::make(__('common.fields.is_active')),
                                TableColumn::make(__('products.fields.stock_on_hand')),
                            ])
                            ->schema([
                                TextEntry::make('size')->placeholder('—'),
                                TextEntry::make('color')->placeholder('—'),
                                TextEntry::make('sku'),
                                TextEntry::make('barcode')->placeholder('—'),
                                IconEntry::make('is_active')->boolean(),
                                TextEntry::make('stock')
                                    ->state(fn (ProductVariant $record) => $record->stockOnHand()),
                            ]),
                    ]),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
