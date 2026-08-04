<?php

namespace App\Filament\Business\Resources\Staff\Pages;

use App\Filament\Business\Resources\Staff\StaffResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'tenant_id' => tenant('id'),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['name' => $user->name, 'role' => $data['role']])
            ->log("Staff account created: {$user->name}");

        return $user;
    }
}
