<x-filament-panels::page x-on:open-receipt.window="window.open($event.detail.url, '_blank')">
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <label for="scanInput" class="text-sm font-medium text-gray-950 dark:text-white">
            Scan or enter barcode / SKU
        </label>
        <input
            type="text"
            id="scanInput"
            wire:model="scanInput"
            wire:keydown.enter.prevent="addByScan"
            autofocus
            autocomplete="off"
            placeholder="Scan a barcode or type a SKU, then press Enter"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:text-sm"
        />
    </div>

    {{ $this->content }}
</x-filament-panels::page>
