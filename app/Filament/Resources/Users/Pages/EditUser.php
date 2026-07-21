<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\BarcodeSetting;
use App\Models\Location;
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

                $barcode = BarcodeSetting::current();
                $data['barcode_format'] = $barcode->format;
                $data['barcode_prefix'] = $barcode->prefix;
                $data['barcode_next_number'] = $barcode->next_number;

                $data['locations'] = Location::query()
                    ->get(['id', 'name', 'type', 'is_active'])
                    ->map(fn (Location $location) => $location->only(['id', 'name', 'type', 'is_active']))
                    ->all();
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

        if ($tenant = $this->record->tenant) {
            tenancy()->initialize($tenant);

            try {
                if (isset($this->data['tax_rate'])) {
                    TaxSetting::current()->update(['rate' => $this->data['tax_rate']]);
                }

                if (isset($this->data['barcode_format'])) {
                    BarcodeSetting::current()->update([
                        'format' => $this->data['barcode_format'],
                        'prefix' => $this->data['barcode_prefix'] ?? null,
                        'next_number' => $this->data['barcode_next_number'],
                    ]);
                }

                if (isset($this->data['locations'])) {
                    $keptIds = [];

                    foreach ($this->data['locations'] as $item) {
                        $location = Location::updateOrCreate(
                            ['id' => $item['id'] ?? null],
                            [
                                'name' => $item['name'],
                                'type' => $item['type'],
                                'is_active' => $item['is_active'] ?? true,
                            ]
                        );

                        $keptIds[] = $location->id;
                    }

                    Location::query()->whereNotIn('id', $keptIds)->delete();
                }
            } finally {
                tenancy()->end();
            }
        }
    }

}
