<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Exports\ImportTemplateExport;
use App\Filament\Business\Concerns\HandlesExcelImportNotifications;
use App\Imports\OpeningStockImport;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use UnitEnum;

class OpeningStockEntry extends Page
{
    use HandlesExcelImportNotifications;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::StoreKeeper]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventory;

    protected static ?int $navigationSort = 44;

    protected string $view = 'filament.business.pages.opening-stock-entry';

    public static function getNavigationLabel(): string
    {
        return __('opening_stock_entry.navigation_label');
    }

    public function getTitle(): string
    {
        return __('opening_stock_entry.navigation_label');
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'lines' => [],
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('lines')
                    ->label(__('opening_stock_entry.navigation_label'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('common.fields.product'))
                            ->options(fn () => Product::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('location_id')
                            ->label(__('common.fields.location'))
                            ->options(fn () => Location::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->label(__('common.fields.quantity'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('batch_number')
                            ->label(__('purchase_orders.fields.batch_number')),
                        DatePicker::make('expiry_date')
                            ->label(__('product_batches.fields.expiry_date'))
                            ->helperText(__('product_batches.expiry_date_helper')),
                    ])
                    ->columns(5)
                    ->addActionLabel(__('common.fields.add_product'))
                    ->minItems(1)
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            foreach ($state['lines'] as $line) {
                $batch = ProductBatch::create([
                    'product_id' => $line['product_id'],
                    'location_id' => $line['location_id'],
                    'batch_number' => $line['batch_number'] ?? null,
                    'quantity' => $line['quantity'],
                    'expiry_date' => $line['expiry_date'] ?? null,
                ]);

                StockMovement::record(
                    batch: $batch,
                    type: StockMovementType::OpeningStock,
                    quantityChange: (float) $line['quantity'],
                );
            }
        });

        Notification::make()
            ->title(__('opening_stock_entry.saved'))
            ->success()
            ->send();

        $this->mount();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.actions.save'))
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadOpeningStockImportTemplate')
                ->label(__('opening_stock_entry.actions.download_import_template'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    new ImportTemplateExport(
                        ['SKU', 'Location', 'Quantity', 'Batch number', 'Expiry date'],
                        [['SKU-0001', 'Main Warehouse', 100, 'BATCH-001', '2027-01-01']],
                    ),
                    'opening-stock-import-template.xlsx'
                )),
            Action::make('importOpeningStock')
                ->label(__('opening_stock_entry.actions.import_excel'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->schema([
                    FileUpload::make('file')
                        ->label(__('common.fields.file'))
                        ->required()
                        ->disk('local')
                        ->directory('imports/'.tenant('id'))
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(5120)
                        ->helperText(__('opening_stock_entry.import.file_helper')),
                ])
                ->action(function (array $data): void {
                    $path = $data['file'];
                    $import = new OpeningStockImport();

                    try {
                        Excel::import($import, $path, 'local');
                        $this->notifyImportResult('opening_stock_entry.import', $import->importedCount(), $import->failures());
                    } catch (Throwable $e) {
                        $this->notifyImportException('opening_stock_entry.import', $e);
                    } finally {
                        Storage::disk('local')->delete($path);
                    }
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->key('form-actions'),
                    ]),
            ]);
    }
}
