<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Runs after the form fields are saved to the database.
        $this->record->assignRole(UserRole::Owner);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email_verified_at'] = now(); 
    
        return $data;
    }

}
