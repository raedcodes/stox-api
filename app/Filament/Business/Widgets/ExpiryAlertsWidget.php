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

    protected static ?string $heading = 'Expiring & expired batches';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => ProductBatch::query()
                    ->with(['product', 'location'])
                    ->where('is_active', true)
                    ->where('quantity', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addDays(30))
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Location'),
                TextColumn::make('batch_number')
                    ->label('Batch')
                    ->placeholder('No batch #'),
                TextColumn::make('quantity'),
                TextColumn::make('expiry_date')
                    ->date()
                    ->badge()
                    ->color(fn (ProductBatch $record) => $record->expiry_date->isPast() ? 'danger' : 'warning'),
            ])
            ->defaultSort('expiry_date', 'asc')
            ->paginated([5, 10, 25]);
    }
}
