<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Checkbox::make('is_active')
            ]);
    }
}
