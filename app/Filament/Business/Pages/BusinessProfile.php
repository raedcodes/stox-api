<?php

namespace App\Filament\Business\Pages;

use App\Enums\BusinessType;
use App\Enums\Currency;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BusinessProfile extends Page
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Owner);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.business.pages.business-profile';

    public static function getNavigationLabel(): string
    {
        return __('settings.business_profile.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.business_profile.navigation_label');
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $tenant = $this->getBusinessTenant();

        $this->form->fill([
            'name' => $tenant->name,
            'logo' => $tenant->logo,
            'address' => $tenant->address,
            'tax_number' => $tenant->tax_number,
            'business_type' => $tenant->business_type,
            'currency' => $tenant->currency,
            'timezone' => $tenant->timezone,
            'domain' => $tenant->domain?->domain,
        ]);
    }

    protected function getBusinessTenant(): Tenant
    {
        return Tenant::findOrFail(tenant('id'));
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('settings.business_profile.fields.business_name'))
                    ->required(),
                FileUpload::make('logo')
                    ->image()
                    ->directory(fn () => 'tenant-logos/'.tenant('id'))
                    ->visibility('public'),
                Textarea::make('address')
                    ->label(__('common.fields.address'))
                    ->rows(2),
                TextInput::make('tax_number')
                    ->label(__('settings.business_profile.fields.tax_number')),
                Select::make('business_type')
                    ->label(__('settings.business_profile.fields.business_type'))
                    ->options(BusinessType::options())
                    ->required(),
                Select::make('currency')
                    ->label(__('settings.business_profile.fields.currency'))
                    ->options(Currency::options())
                    ->required(),
                Select::make('timezone')
                    ->label(__('settings.business_profile.fields.timezone'))
                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                    ->searchable()
                    ->required(),
                TextInput::make('domain')
                    ->label(__('settings.business_profile.fields.subdomain'))
                    ->disabled(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $tenant = $this->getBusinessTenant();

        $tenant->update([
            'name' => $data['name'],
            'logo' => $data['logo'],
            'address' => $data['address'],
            'tax_number' => $data['tax_number'],
            'business_type' => $data['business_type'],
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
        ]);

        Notification::make()
            ->title(__('settings.business_profile.saved'))
            ->success()
            ->send();
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
