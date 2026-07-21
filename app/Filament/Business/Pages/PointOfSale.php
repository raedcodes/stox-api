<?php

namespace App\Filament\Business\Pages;

use App\DiscountType;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\PaymentMethod;
use App\SaleStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
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
use RuntimeException;

class PointOfSale extends Page
{
    protected string $view = 'filament.business.pages.point-of-sale';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $scanInput = '';

    public function mount(): void
    {
        $this->form->fill([
            'customer_id' => Customer::walkIn()->id,
            'location_id' => Location::query()->where('is_active', true)->first()?->id,
            'payment_method' => PaymentMethod::Cash,
            'discount_type' => null,
            'discount_value' => null,
            'items' => [],
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
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_method')
                    ->options(PaymentMethod::options())
                    ->required(),
                Select::make('discount_type')
                    ->label('Invoice discount type')
                    ->options(DiscountType::options()),
                TextInput::make('discount_value')
                    ->label('Invoice discount value')
                    ->numeric()
                    ->minValue(0),
                Repeater::make('items')
                    ->label('Cart')
                    ->schema([
                        Hidden::make('product_id'),
                        TextInput::make('product_name')
                            ->label('Product')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')),
                        Select::make('discount_type')
                            ->label('Discount type')
                            ->options(DiscountType::options()),
                        TextInput::make('discount_value')
                            ->label('Discount value')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(6)
                    ->addActionLabel('Add product manually')
                    ->reorderable(false),
            ]);
    }

    /**
     * Look up the scanned barcode/SKU and add it to the cart (or bump its
     * quantity if it's already there).
     */
    public function addByScan(): void
    {
        $term = trim($this->scanInput);
        $this->scanInput = '';

        if (blank($term)) {
            return;
        }

        $product = Product::query()
            ->where('barcode', $term)
            ->orWhere('sku', $term)
            ->first();

        if (! $product) {
            Notification::make()
                ->title("No product found for \"{$term}\"")
                ->warning()
                ->send();

            return;
        }

        $items = $this->data['items'] ?? [];

        foreach ($items as $key => $item) {
            if ((int) ($item['product_id'] ?? null) === $product->id) {
                $items[$key]['quantity'] = (float) ($item['quantity'] ?? 0) + 1;
                $this->data['items'] = $items;

                return;
            }
        }

        $items[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => (float) $product->selling_price,
            'discount_type' => null,
            'discount_value' => null,
        ];

        $this->data['items'] = $items;
    }

    public function checkout(): void
    {
        $state = $this->form->getState();

        if (empty($state['items'])) {
            Notification::make()
                ->title('Cart is empty')
                ->warning()
                ->send();

            return;
        }

        try {
            $sale = DB::transaction(function () use ($state) {
                $sale = Sale::create([
                    'customer_id' => $state['customer_id'],
                    'location_id' => $state['location_id'],
                    'payment_method' => $state['payment_method'],
                    'discount_type' => $state['discount_type'] ?? null,
                    'discount_value' => $state['discount_value'] ?? null,
                    'sale_date' => now(),
                    'status' => SaleStatus::Draft,
                ]);

                foreach ($state['items'] as $item) {
                    $sale->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_type' => $item['discount_type'] ?? null,
                        'discount_value' => $item['discount_value'] ?? null,
                    ]);
                }

                $sale->complete();

                return $sale;
            });
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('Cannot complete sale')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title("Sale {$sale->sale_number} completed")
            ->success()
            ->send();

        $this->dispatch('open-receipt', url: route('business.sales.invoice', $sale));

        $this->mount();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('checkout')
                ->label('Complete Sale')
                ->submit('checkout'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('checkout')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->key('form-actions'),
                    ]),
            ]);
    }
}
