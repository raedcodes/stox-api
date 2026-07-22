<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{ $this->form }}
    </div>

    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-sm">
            <tr class="border-b border-gray-200 dark:border-white/10">
                <td class="py-2 text-gray-500 dark:text-gray-400">Revenue</td>
                <td class="py-2 text-right font-medium text-gray-950 dark:text-white">{{ tenant('currency')?->value }} {{ number_format($this->revenue(), 2) }}</td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-white/10">
                <td class="py-2 text-gray-500 dark:text-gray-400">Cost of goods sold</td>
                <td class="py-2 text-right font-medium text-gray-950 dark:text-white">-{{ tenant('currency')?->value }} {{ number_format($this->cogs(), 2) }}</td>
            </tr>
            <tr class="border-b-2 border-gray-950 dark:border-white">
                <td class="py-2 font-semibold text-gray-950 dark:text-white">Gross profit</td>
                <td class="py-2 text-right font-semibold text-gray-950 dark:text-white">{{ tenant('currency')?->value }} {{ number_format($this->grossProfit(), 2) }}</td>
            </tr>
            <tr>
                <td class="py-2 text-gray-500 dark:text-gray-400">Gross margin</td>
                <td class="py-2 text-right font-medium text-gray-950 dark:text-white">{{ number_format($this->grossMarginPercent(), 1) }}%</td>
            </tr>
        </table>

        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
            This is a gross profit figure (revenue minus cost of goods sold). It does not include operating expenses, which are not tracked in the system.
        </p>
    </div>
</x-filament-panels::page>
