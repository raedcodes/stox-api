<?php

namespace App\Filament\Business\Pages;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PointOfSale extends Page
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole([UserRole::Owner, UserRole::Manager, UserRole::Cashier]);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.business.pages.point-of-sale';

    public static function getNavigationLabel(): string
    {
        return __('pos.navigation_label');
    }

    public function getTitle(): string
    {
        return __('pos.title');
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public string $scanInput = '';

    public ?string $lastAddedLabel = null;

    public function mount(): void
    {
        $this->form->fill([
            'customer_id' => Customer::walkIn()->id,
            'location_id' => session('current_location_id') ?? Location::query()->where('is_active', true)->first()?->id,
            'payment_method' => PaymentMethod::Cash->value,
            'discount_type' => null,
            'discount_value' => null,
            'items' => [],
        ]);

        $this->lastAddedLabel = null;
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
                    ->label(__('common.fields.customer'))
                    ->options(fn () => Customer::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('location_id')
                    ->label(__('common.fields.location'))
                    ->options(fn () => Location::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_method')
                    ->label(__('common.fields.payment_method'))
                    ->options(PaymentMethod::options())
                    ->required(),
                Select::make('discount_type')
                    ->label(__('sales.fields.invoice_discount_type'))
                    ->options(DiscountType::options()),
                TextInput::make('discount_value')
                    ->label(__('sales.fields.invoice_discount_value'))
                    ->numeric()
                    ->minValue(0),
                Repeater::make('items')
                    ->label(__('pos.cart'))
                    ->schema([
                        Select::make('product_id')
                            ->label(__('common.fields.product'))
                            ->options(fn () => Product::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('unit_price', Product::find($state)?->selling_price);
                                $set('product_variant_id', ProductVariant::soleActiveIdFor($state));
                            }),
                        Select::make('product_variant_id')
                            ->label(__('common.fields.variant'))
                            ->options(fn (Get $get) => ProductVariant::activeOptionsFor($get('product_id')))
                            ->visible(fn (Get $get) => ProductVariant::hasMultipleActiveFor($get('product_id')))
                            ->required(fn (Get $get) => ProductVariant::hasMultipleActiveFor($get('product_id')))
                            ->dehydratedWhenHidden(),
                        TextInput::make('quantity')
                            ->label(__('common.fields.quantity'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_price')
                            ->label(__('common.fields.unit_price'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix(fn () => tenant('currency')?->value),
                        Select::make('discount_type')
                            ->label(__('common.fields.discount_type'))
                            ->options(DiscountType::options()),
                        TextInput::make('discount_value')
                            ->label(__('common.fields.discount_value'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(7)
                    ->addActionLabel(__('pos.add_product_manually'))
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

        if (blank($term)) {
            return;
        }

        $product = Product::query()
            ->where('barcode', $term)
            ->orWhere('sku', $term)
            ->first();

        if (! $product) {
            Notification::make()
                ->title(__('pos.no_product_found', ['term' => $term]))
                ->warning()
                ->send();

            return;
        }

        $this->addProductToCart($product, $term);
    }

    /**
     * Add a product picked from the search-as-you-type results to the cart.
     */
    public function addProductById(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $this->addProductToCart($product, $product->sku);
    }

    /**
     * Products matching the current scan input, for the search-as-you-type
     * results list. Blank input returns no matches.
     */
    public function getScanMatchesProperty()
    {
        $term = trim($this->scanInput);

        if (blank($term)) {
            return collect();
        }

        return Product::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            })
            ->limit(8)
            ->get();
    }

    protected function addProductToCart(Product $product, string $matchedTerm): void
    {
        $items = $this->data['items'] ?? [];

        foreach ($items as $key => $item) {
            if ((int) ($item['product_id'] ?? null) === $product->id) {
                $items[$key]['quantity'] = (float) ($item['quantity'] ?? 0) + 1;
                $this->data['items'] = $items;
                $this->form->fill($this->data);
                $this->finishAddingToCart($product, $matchedTerm);

                return;
            }
        }

        $items[] = [
            'product_id' => $product->id,
            'product_variant_id' => ProductVariant::soleActiveIdFor($product->id),
            'quantity' => 1,
            'unit_price' => (float) $product->selling_price,
            'discount_type' => null,
            'discount_value' => null,
        ];

        $this->data['items'] = $items;
        $this->form->fill($this->data);
        $this->finishAddingToCart($product, $matchedTerm);
    }

    protected function finishAddingToCart(Product $product, string $matchedTerm): void
    {
        $this->lastAddedLabel = "{$matchedTerm} \u{2192} {$product->name}";
        $this->scanInput = '';
        $this->dispatch('scan-input-focus');
    }

    public function checkout(): void
    {
        $state = $this->form->getState();

        if (empty($state['items'])) {
            Notification::make()
                ->title(__('pos.cart_empty'))
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
                        'product_variant_id' => $item['product_variant_id'] ?? null,
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
                ->title(__('sales.notifications.cannot_complete'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('pos.sale_completed', ['number' => $sale->sale_number]))
            ->success()
            ->send();

        $this->dispatch('open-receipt', url: route('business.sales.invoice', $sale));

        $this->mount();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('checkout')
                ->label(__('pos.complete_sale'))
                ->submit('checkout')
                ->keyBindings(['mod+enter']),
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
