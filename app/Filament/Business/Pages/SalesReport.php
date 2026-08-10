<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Exports\SalesReportExport;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.business.pages.sales-report';

    protected function baseQuery(): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed)
            ->select('sale_items.*');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->baseQuery())
            ->columns([
                TextColumn::make('sale.sale_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('sale.sale_number')
                    ->label('Invoice #'),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('product.brand.name')
                    ->label('Brand')
                    ->placeholder('—'),
                TextColumn::make('product.category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('sale.cashier.name')
                    ->label('Cashier')
                    ->placeholder('—'),
                TextColumn::make('sale.location.name')
                    ->label('Location')
                    ->placeholder('—'),
                TextColumn::make('quantity'),
                TextColumn::make('total')
                    ->label('Line total')
                    ->state(fn (SaleItem $record) => $record->total())
                    ->money(fn () => tenant('currency')?->value),
            ])
            ->groups([
                Group::make('sale.sale_date')
                    ->label('Date')
                    ->date(),
                Group::make('product.name')
                    ->label('Product'),
                Group::make('product.category.name')
                    ->label('Category'),
                Group::make('sale.cashier.name')
                    ->label('Cashier'),
                Group::make('sale.location.name')
                    ->label('Location'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('sales.sale_date', '<=', $date));
                    }),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $locationId) => $q->where('sales.location_id', $locationId)
                    )),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $categoryId) => $q->whereHas('product', fn ($q) => $q->where('category_id', $categoryId))
                    )),
                SelectFilter::make('created_by')
                    ->label('Cashier')
                    // created_by is polymorphic (a staff member or the central
                    // Owner) — a plain id can collide between the two tables,
                    // so options/filtering both carry the type alongside it.
                    ->options(fn () => Staff::query()->pluck('name', 'id')
                        ->mapWithKeys(fn ($name, $id) => ["staff:{$id}" => $name])
                        ->union(
                            User::query()->where('tenant_id', tenant('id'))->pluck('name', 'id')
                                ->mapWithKeys(fn ($name, $id) => ["user:{$id}" => $name])
                        ))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        function (Builder $q, string $value) {
                            [$type, $id] = explode(':', $value, 2);

                            $q->where('sales.created_by', $id)
                                ->where('sales.creator_type', $type === 'staff' ? Staff::class : User::class);
                        }
                    )),
            ])
            ->defaultSort('sale.sale_date', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->action(fn () => $this->exportPdf()),
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => Excel::download(
                    new SalesReportExport($this->getFilteredTableQuery()),
                    'sales-report.xlsx'
                )),
        ];
    }

    protected function exportPdf(): StreamedResponse
    {
        $items = $this->getFilteredTableQuery()->with(['product.category', 'product.brand', 'sale.cashier', 'sale.location'])->get();

        $pdf = Pdf::loadView('pdf.reports.sales', [
            'title' => 'Sales Report',
            'subtitle' => null,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
            'items' => $items,
        ]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'sales-report.pdf');
    }
}
