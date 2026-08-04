<?php

namespace App\Filament\Business\Resources\Staff\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class StaffForm
{
    /**
     * Roles assignable to staff created here — deliberately excludes Owner
     * (there's already exactly one, created when the business itself was
     * set up) and Super Admin (platform-level, not a business role at all).
     */
    public static function assignableRoles(): array
    {
        return [
            UserRole::Manager->value => UserRole::Manager->getLabel(),
            UserRole::Cashier->value => UserRole::Cashier->getLabel(),
            UserRole::StoreKeeper->value => UserRole::StoreKeeper->getLabel(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true),
                Select::make('role')
                    ->label('Role')
                    ->options(self::assignableRoles())
                    ->default(fn (?User $record) => $record?->getRoleNames()->first())
                    ->required(),
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
                    ->label('Confirm password')
                    ->password()
                    ->autocomplete('new-password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Checkbox::make('is_active')
                    ->default(true),
            ]);
    }
}
