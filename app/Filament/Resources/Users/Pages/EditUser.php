<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\TaxSetting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password'], $data['role']);

        if ($tenant = $this->record->tenant) {
            tenancy()->initialize($tenant);

            try {
                $data['tax_rate'] = TaxSetting::current()->rate;
            } finally {
                tenancy()->end();
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->data['role'])) {
            $this->record->syncRoles($this->data['role']);
        }

        if (($tenant = $this->record->tenant) && isset($this->data['tax_rate'])) {
            tenancy()->initialize($tenant);

            try {
                TaxSetting::current()->update(['rate' => $this->data['tax_rate']]);
            } finally {
                tenancy()->end();
            }
        }
    }

}
