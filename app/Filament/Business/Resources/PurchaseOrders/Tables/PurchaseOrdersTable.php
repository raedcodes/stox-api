<?php

namespace App\Filament\Business\Resources\PurchaseOrders\Tables;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Business\Resources\PurchaseOrders\Actions\PurchaseOrderWorkflowActions;
use App\Filament\Business\Resources\PurchaseOrders\Actions\ReceiveStockAction;
use App\Filament\Business\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['supplier', 'location'])
                // Precomputed here instead of calling ->total() per row: that
                // method always issues its own two aggregate queries (items +
                // charges) regardless of eager loading, since it queries the
                // relation builder directly rather than a loaded collection.
                ->selectRaw('purchase_orders.*, (
                    (select coalesce(sum(quantity_ordered * unit_cost), 0) from purchase_order_items where purchase_order_items.purchase_order_id = purchase_orders.id)
                    + (select coalesce(sum(amount), 0) from purchase_order_charges where purchase_order_charges.purchase_order_id = purchase_orders.id)
                ) as computed_total'))
            ->columns([
                TextColumn::make('po_number')
                    ->label(__('common.fields.po_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label(__('common.fields.supplier'))
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label(__('purchase_orders.fields.deliver_to')),
                TextColumn::make('status')
                    ->label(__('common.fields.status'))
                    ->badge(),
                TextColumn::make('order_date')
                    ->label(__('common.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('expected_date')
                    ->label(__('purchase_orders.fields.expected_date'))
                    ->date()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label(__('common.fields.total'))
                    ->state(fn (PurchaseOrder $record) => $record->computed_total)
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->recordUrl(fn (PurchaseOrder $record) => PurchaseOrderResource::getUrl('view', ['record' => $record]))
            ->defaultSort('order_date', 'desc')
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label(__('common.fields.supplier'))
                    ->options(fn () => Supplier::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label(__('common.fields.status'))
                    ->options(PurchaseOrderStatus::options()),
            ])
            ->recordActions([
                Action::make('printPo')
                    ->label(__('purchase_orders.actions.print_po'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (PurchaseOrder $record) => route('business.purchase-orders.pdf', $record))
                    ->openUrlInNewTab(),
                PurchaseOrderWorkflowActions::submitForApproval(),
                PurchaseOrderWorkflowActions::approve(),
                PurchaseOrderWorkflowActions::reject(),
                ReceiveStockAction::make(),
                ActionGroup::make([
                    PurchaseOrderWorkflowActions::cancel(),
                    EditAction::make()
                        ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft),
                    DeleteAction::make()
                        ->visible(fn (PurchaseOrder $record) => $record->status === PurchaseOrderStatus::Draft),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
