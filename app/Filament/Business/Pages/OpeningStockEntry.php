<?php

namespace App\Filament\Business\Pages;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
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
use Illuminate\Support\Facades\DB;

class OpeningStockEntry extends Page
{
    protected string $view = 'filament.business.pages.opening-stock-entry';

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
                    ->label('Opening stock')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn () => Product::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('location_id')
                            ->label('Location')
                            ->options(fn () => Location::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('batch_number')
                            ->label('Batch #'),
                        DatePicker::make('expiry_date')
                            ->helperText('Leave empty if this batch doesn\'t expire.'),
                    ])
                    ->columns(5)
                    ->addActionLabel('Add product')
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
            ->title('Opening stock recorded')
            ->success()
            ->send();

        $this->mount();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
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
