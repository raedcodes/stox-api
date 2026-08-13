<?php

namespace App\Filament\Business\Resources\Staff\Schemas;

use App\Enums\UserRole;
use App\Models\Staff;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->label(__('common.fields.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('staff.fields.email_address'))
                    ->email()
                    ->required()
                    // Unique within this tenant's own `staff` table only — two
                    // different businesses can each have a staff member with
                    // the same email address.
                    ->unique(table: 'staff', column: 'email', ignoreRecord: true),
                Select::make('role')
                    ->label(__('staff.fields.role'))
                    ->options(self::assignableRoles())
                    ->default(fn (?Staff $record) => $record?->getRoleNames()->first())
                    ->helperText(__('staff.role_helper'))
                    ->required(),
                TextInput::make('password')
                    ->label(__('staff.fields.password'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->confirmed()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): string => $operation === 'create'
                        ? __('staff.password_helper_create')
                        : __('staff.password_helper_edit'))
                    ->rules([
                        Password::min(8)
                            ->letters()
                            ->mixedCase()
                            ->numbers()
                            ->symbols()
                            ->uncompromised(),
                    ]),
                TextInput::make('password_confirmation')
                    ->label(__('staff.fields.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Toggle::make('is_active')
                    ->label(__('common.fields.is_active'))
                    ->helperText(__('staff.is_active_helper'))
                    ->default(true),
            ]);
    }
}
