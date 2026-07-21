<?php

namespace App\Filament\Resources\Users\Schemas;

use App\BusinessType;
use App\Currency;
use App\Models\User;
use App\UserRole;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                // DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->autocomplete('new-password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->confirmed()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->rules([
                        Password::min(8)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols()
                            ->uncompromised(),
                    ]),
                TextInput::make('password_confirmation')
                    ->label('Confirmed Password')
                    ->password()
                    ->autocomplete('new-password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Select::make('role')
                    ->label('Role')
                    ->visibleOn('edit')
                    ->default(fn (?User $record) => $record?->getRoleNames()->first() ?? UserRole::Owner)
                    ->options(UserRole::options()),
                Checkbox::make('is_active'),
                Section::make('Business')
                    ->relationship('tenant')
                    ->visibleOn('edit')
                    ->schema([
                        TextInput::make('name')
                            ->label('Business name')
                            ->required(),
                        FileUpload::make('logo')
                            ->image()
                            ->directory('tenant-logos')
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
                        Section::make('Domain')
                            ->relationship('domain')
                            ->schema([
                                TextInput::make('domain')
                                    ->label('Subdomain')
                                    ->required()
                                    ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                                    ->validationMessages([
                                        'regex' => 'Only lowercase letters, numbers, and single hyphens are allowed.',
                                    ])
                                    ->unique(table: 'domains', column: 'domain', ignoreRecord: true),
                            ]),
                    ]),
            ]);
    }
}
