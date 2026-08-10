<x-filament-widgets::widget>
    <x-filament::section>
        {{--
            Structural layout (stacking + spacing) is set via inline styles,
            not Tailwind utility classes — if the frontend asset bundle
            hasn't been rebuilt (`npm run build`) since these classes were
            added, Tailwind never generates their CSS and they're inert.
            Inline styles always apply regardless of build state. Filament's
            own classes (fi-select-input, label/text colors) are kept for
            visuals, since those come from Filament's already-compiled
            first-party stylesheet, not this app's own Tailwind build.
        --}}
        <div style="display: flex; flex-direction: column; row-gap: 0.625rem;">
            <label
                for="current-location"
                class="text-sm font-medium text-gray-950 dark:text-white"
                style="display: block;"
            >
                Working from
            </label>

            <x-filament::input.wrapper style="display: block; width: 20rem; max-width: 100%;">
                <select
                    id="current-location"
                    wire:model.live="locationId"
                    class="fi-select-input"
                    style="width: 100%;"
                >
                    <option value="">All Locations</option>
                    @foreach ($this->getLocations() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>

            <p
                class="text-xs text-gray-400 dark:text-gray-500"
                style="display: block; margin: 0;"
            >
                Scopes this dashboard and defaults into POS, purchase orders, and transfers — still changeable per form.
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
