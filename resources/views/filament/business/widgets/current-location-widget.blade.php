<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-2.5">
            <label for="current-location" class="text-sm font-medium text-gray-950 dark:text-white">
                Working from
            </label>

            <x-filament::input.wrapper class="w-72">
                <select
                    id="current-location"
                    wire:model.live="locationId"
                    class="fi-select-input"
                >
                    <option value="">All Locations</option>
                    @foreach ($this->getLocations() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>

            <p class="text-xs text-gray-400 dark:text-gray-500">
                Scopes this dashboard and defaults into POS, purchase orders, and transfers — still changeable per form.
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
