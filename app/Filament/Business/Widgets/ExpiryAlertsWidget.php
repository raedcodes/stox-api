<?php

namespace App\Filament\Business\Widgets;

use App\Enums\UserRole;
use App\Models\ProductBatch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiryAlertsWidget extends TableWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.widgets.expiry_alerts_heading'))
            ->query(
                // Null means "All Locations" (the CurrentLocationWidget
                // default) — stays unscoped in that case.
                fn (): Builder => ProductBatch::query()
                    ->with(['product', 'location'])
                    ->expiringWithin(30, session('current_location_id'))
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('common.fields.product'))
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label(__('common.fields.location')),
                TextColumn::make('batch_number')
                    ->label(__('common.fields.batch'))
                    ->placeholder(__('common.fields.no_batch_number')),
                TextColumn::make('quantity')
                    ->label(__('common.fields.quantity')),
                TextColumn::make('expiry_date')
                    ->label(__('product_batches.fields.expiry_date'))
                    ->date()
                    ->badge()
                    ->color(fn (ProductBatch $record) => $record->expiry_date->isPast() ? 'danger' : 'warning'),
            ])
            ->defaultSort('expiry_date', 'asc')
            ->paginated([5, 10, 25]);
    }
}
