<x-filament-panels::page
    x-on:open-receipt.window="window.open($event.detail.url, '_blank')"
    x-on:scan-input-focus.window="document.getElementById('scanInput')?.focus()"
>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <label for="scanInput" class="mb-1 block text-sm font-medium text-gray-950 dark:text-white">
            Scan or enter barcode / SKU
        </label>
        <div
            class="relative"
            x-data="{ highlight: 0, matchCount: {{ $this->scanMatches->count() }} }"
            x-effect="matchCount = {{ $this->scanMatches->count() }}; if (highlight >= matchCount) highlight = 0"
        >
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    id="scanInput"
                    wire:model.live.debounce.300ms="scanInput"
                    autofocus
                    autocomplete="off"
                    placeholder="Scan a barcode or type an SKU, then press Enter"
                    x-on:keydown.arrow-down.prevent="highlight = matchCount ? (highlight + 1) % matchCount : 0"
                    x-on:keydown.arrow-up.prevent="highlight = matchCount ? (highlight - 1 + matchCount) % matchCount : 0"
                    x-on:keydown.enter.prevent="matchCount ? document.querySelector('[data-match-index=' + highlight + ']')?.click() : $wire.addByScan()"
                />
            </x-filament::input.wrapper>

            @if (trim($scanInput) !== '' && $this->scanMatches->isNotEmpty())
                <ul class="fi-dropdown-list absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-300 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-800">
                    @foreach ($this->scanMatches as $match)
                        <li>
                            <button
                                type="button"
                                data-match-index="{{ $loop->index }}"
                                wire:click="addProductById({{ $match->id }})"
                                :class="{ 'bg-gray-100 dark:bg-gray-700': highlight === {{ $loop->index }} }"
                                class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                            >
                                <span class="font-medium">{{ $match->name }}</span>
                                <span class="text-gray-500 dark:text-gray-400">&mdash; {{ $match->sku ?? $match->barcode }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($lastAddedLabel)
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Added: {{ $lastAddedLabel }}</p>
        @endif
    </div>

    {{ $this->content }}
</x-filament-panels::page>
