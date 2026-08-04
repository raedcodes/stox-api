<?php

namespace App\Filament\Business\Pages;

use App\Enums\BarcodeFormat;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Models\BarcodeSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BarcodeSettings extends Page
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Owner);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 62;

    protected string $view = 'filament.business.pages.barcode-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $barcode = BarcodeSetting::current();

        $this->form->fill([
            'format' => $barcode->format,
            'prefix' => $barcode->prefix,
            'next_number' => $barcode->next_number,
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
                Select::make('format')
                    ->label('Barcode format')
                    ->options(BarcodeFormat::options())
                    ->required(),
                TextInput::make('prefix')
                    ->label('Prefix')
                    ->maxLength(20)
                    ->helperText('Optional prefix included at the start of every generated barcode.'),
                TextInput::make('next_number')
                    ->label('Next number')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->helperText('The sequence number the next generated barcode will use.'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        BarcodeSetting::current()->update([
            'format' => $data['format'],
            'prefix' => $data['prefix'] ?? null,
            'next_number' => $data['next_number'],
        ]);

        Notification::make()
            ->title('Barcode settings saved')
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
