<?php

namespace App\Filament\Business\Resources\Staff\Pages;

use App\Filament\Business\Resources\Staff\StaffResource;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    /**
     * No delete action: staff removal is deactivation (is_active = false),
     * never a hard delete. Users live in the central database while their
     * sales/stock-movement/activity-log references live in each tenant
     * database, so there's no foreign key to protect those records —
     * deleting a staff account would silently orphan every sale, stock
     * movement, and activity log entry they ever caused.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->data['role'])) {
            $this->record->syncRoles($this->data['role']);
        }
    }
}
