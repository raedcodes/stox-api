<?php

namespace App\Filament\Business\Widgets;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Purchase orders currently waiting on this StoreKeeper to receive —
 * unlike the generic KPI/chart widgets (Owner/Manager-only), this is
 * directly relevant to what a StoreKeeper actually does day to day.
 */
class PendingPurchaseOrdersWidget extends TableWidget
{
    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.widgets.pending_purchase_orders_heading'))
            ->query(fn (): Builder => PurchaseOrder::query()
                ->whereIn('status', [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived]))
            ->columns([
                TextColumn::make('po_number')
                    ->label(__('common.fields.po_number')),
                TextColumn::make('supplier.name')
                    ->label(__('common.fields.supplier')),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->badge(),
                TextColumn::make('expected_date')
                    ->label(__('purchase_orders.fields.expected_date'))
                    ->date()
                    ->placeholder('—'),
            ])
            ->recordUrl(fn (PurchaseOrder $record) => PurchaseOrderResource::getUrl('view', ['record' => $record]))
            ->defaultSort('expected_date', 'asc')
            ->paginated([5, 10, 25]);
    }
}
