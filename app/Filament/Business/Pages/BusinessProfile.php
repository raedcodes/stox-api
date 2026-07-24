<?php

namespace App\Filament\Business\Pages;

use App\Enums\BusinessType;
use App\Enums\Currency;
use App\Models\Tenant;
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

class BusinessProfile extends Page
{
    protected string $view = 'filament.business.pages.business-profile';

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
                    ->label('Business name')
                    ->required(),
                FileUpload::make('logo')
                    ->image()
                    ->directory(fn () => 'tenant-logos/'.tenant('id'))
                    ->visibility('public'),
                Textarea::make('address')
                    ->rows(2),
                TextInput::make('tax_number')
                    ->label('Tax number'),
                Select::make('business_type')
                    ->label('Business type')
                    ->options(BusinessType::options())
                    ->required(),
                Select::make('currency')
                    ->options(Currency::options())
                    ->required(),
                Select::make('timezone')
                    ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                    ->searchable()
                    ->required(),
                TextInput::make('domain')
                    ->label('Subdomain')
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
            ->title('Business profile saved')
            ->success()
            ->send();
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
