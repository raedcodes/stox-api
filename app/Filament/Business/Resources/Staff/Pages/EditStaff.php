<?php

namespace App\Filament\Business\Resources\Staff\Pages;

use App\Filament\Business\Resources\Staff\StaffResource;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    /**
     * No delete action: staff removal is deactivation (is_active = false),
     * never a hard delete. sales.created_by/stock_movements.performed_by are
     * polymorphic (a staff member or the central Owner) with no FK possible
     * across that split, so deleting a staff account would silently orphan
     * every sale, stock movement, and activity log entry they ever caused.
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
