<?php

namespace App\Filament\Business\Resources\Staff\Pages;

use App\Filament\Business\Resources\Staff\StaffResource;
use App\Models\Staff;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $staff = Staff::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($staff)
            ->withProperties(['name' => $staff->name, 'role' => $data['role']])
            ->log("Staff account created: {$staff->name}");

        return $staff;
    }
}
