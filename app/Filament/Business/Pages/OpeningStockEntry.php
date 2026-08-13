<?php

namespace App\Filament\Business\Pages;

use App\Enums\NavigationGroup;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
use UnitEnum;

class OpeningStockEntry extends Page
{
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
