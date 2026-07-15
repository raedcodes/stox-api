<?php

namespace App\Filament\Resources\Users\Pages;

use App\BusinessType;
use App\Filament\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class CreateUser extends CreateRecord
{
    use HasWizard;

    protected static string $resource = UserResource::class;

    public function getSteps(): array
    {
        return [
            Step::make('Business')
                ->schema([
                    TextInput::make('business_name')
                        ->label('Business name')
                        ->required(),
                    TextInput::make('subdomain')
                        ->required()
                        ->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/')
                        ->validationMessages([
                            'regex' => 'Only lowercase letters, numbers, and single hyphens are allowed.',
                        ])
                        ->unique(table: 'domains', column: 'domain')
                        ->helperText('Lowercase letters, numbers, and hyphens only, e.g. "acme" → acme.yourapp.com'),
                ]),
            Step::make('Business Settings')
                ->schema([
                    Select::make('business_type')
                        ->label('Business type')
                        ->options(BusinessType::options())
                        ->required(),
                    Select::make('currency')
                        ->options([
                            'USD' => 'USD - US Dollar',
                            'EUR' => 'EUR - Euro',
                            'GBP' => 'GBP - British Pound',
                            'AED' => 'AED - UAE Dirham',
                            'SAR' => 'SAR - Saudi Riyal',
                            'EGP' => 'EGP - Egyptian Pound',
                            'JOD' => 'JOD - Jordanian Dinar',
                            'LBP' => 'LBP - Lebanese Pound',
                            'CAD' => 'CAD - Canadian Dollar',
                            'AUD' => 'AUD - Australian Dollar',
                        ])
                        ->required()
                        ->default('USD'),
                    Select::make('timezone')
                        ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                        ->searchable()
                        ->required()
                        ->default(config('app.timezone')),
                ]),
            Step::make('Owner Account')
                ->schema([
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('email')
                        ->label('Email address')
                        ->email()
                        ->required()
                        ->unique(table: 'users', column: 'email'),
                    TextInput::make('password')
                        ->password()
                        ->autocomplete('new-password')
                        ->required()
                        ->confirmed()
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
                        ->required()
                        ->dehydrated(false),
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Tenant creation triggers CREATE DATABASE/migrate/seed synchronously, and CREATE
        // DATABASE is DDL that implicitly commits in MySQL — it can't be part of a
        // transaction, so it happens first, on its own.
        $tenant = Tenant::create([
            'name' => $data['business_name'],
            'business_type' => $data['business_type'],
            'currency' => $data['currency'],
            'timezone' => $data['timezone'],
        ]);

        try {
            return DB::transaction(function () use ($tenant, $data) {
                $tenant->domains()->create(['domain' => $data['subdomain']]);

                $owner = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);

                $owner->assignRole(UserRole::Owner);

                return $owner;
            });
        } catch (\Throwable $e) {
            // Deleting the tenant also drops its provisioned database via the
            // TenantDeleted pipeline, so we don't leave an orphaned business behind.
            $tenant->delete();

            throw $e;
        }
    }
}
