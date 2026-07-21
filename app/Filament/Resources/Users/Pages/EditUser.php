<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\BarcodeSetting;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\TaxSetting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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

                $data['brands'] = Brand::query()
                    ->get(['id', 'name', 'is_active'])
                    ->map(fn (Brand $brand) => $brand->only(['id', 'name', 'is_active']))
                    ->all();

                $data['categories'] = Category::query()
                    ->whereNull('parent_id')
                    ->with('children')
                    ->get()
                    ->map(fn (Category $category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'subcategories' => $category->children
                            ->map(fn (Category $child) => $child->only(['id', 'name']))
                            ->all(),
                    ])
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
                    $this->syncFlatList(Location::class, $this->data['locations']);
                }

                if (isset($this->data['brands'])) {
                    $this->syncFlatList(Brand::class, $this->data['brands']);
                }

                if (isset($this->data['categories'])) {
                    $keptIds = [];

                    foreach ($this->data['categories'] as $categoryItem) {
                        $category = Category::updateOrCreate(
                            ['id' => $categoryItem['id'] ?? null],
                            ['name' => $categoryItem['name'], 'parent_id' => null]
                        );

                        $keptIds[] = $category->id;

                        foreach ($categoryItem['subcategories'] ?? [] as $subcategoryItem) {
                            $subcategory = Category::updateOrCreate(
                                ['id' => $subcategoryItem['id'] ?? null],
                                ['name' => $subcategoryItem['name'], 'parent_id' => $category->id]
                            );

                            $keptIds[] = $subcategory->id;
                        }
                    }

                    Category::query()->whereNotIn('id', $keptIds)->delete();
                }
            } finally {
                tenancy()->end();
            }
        }
    }

    /**
     * Sync a flat repeater (id + arbitrary fields, no hierarchy) against a model:
     * update rows that have an id, create rows that don't, and delete anything
     * not present in $items.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function syncFlatList(string $modelClass, array $items): void
    {
        $keptIds = [];

        foreach ($items as $item) {
            $record = $modelClass::updateOrCreate(
                ['id' => $item['id'] ?? null],
                collect($item)->except('id')->all()
            );

            $keptIds[] = $record->id;
        }

        $modelClass::query()->whereNotIn('id', $keptIds)->delete();
    }

}
