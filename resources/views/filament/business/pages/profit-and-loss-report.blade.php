<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{ $this->form }}
    </div>

    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-sm">
            <tr class="border-b border-gray-200 dark:border-white/10">
                <td class="w-1/2 py-2.5 pe-4 text-gray-500 dark:text-gray-400">Revenue</td>
                <td class="py-2.5 ps-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">{{ tenant('currency')?->value }} {{ number_format($this->revenue(), 2) }}</td>
            </tr>
            <tr class="border-b border-gray-200 dark:border-white/10">
                <td class="w-1/2 py-2.5 pe-4 text-gray-500 dark:text-gray-400">Cost of goods sold</td>
                <td class="py-2.5 ps-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">-{{ tenant('currency')?->value }} {{ number_format($this->cogs(), 2) }}</td>
            </tr>
            <tr class="border-b-2 border-gray-950 dark:border-white">
                <td class="w-1/2 py-2.5 pe-4 font-semibold text-gray-950 dark:text-white">Gross profit</td>
                <td class="py-2.5 ps-4 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ tenant('currency')?->value }} {{ number_format($this->grossProfit(), 2) }}</td>
            </tr>
            <tr>
                <td class="w-1/2 py-2.5 pe-4 text-gray-500 dark:text-gray-400">Gross margin</td>
                <td class="py-2.5 ps-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">{{ number_format($this->grossMarginPercent(), 1) }}%</td>
            </tr>
        </table>

        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
            This is a gross profit figure (revenue minus cost of goods sold). It does not include operating expenses, which are not tracked in the system.
        </p>
    </div>

    @php $byLocation = $this->byLocation(); @endphp
    @if ($byLocation->count() > 1)
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">By location</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pe-4 text-left font-medium">Location</th>
                        <th class="py-2 pe-4 text-right font-medium">Revenue</th>
                        <th class="py-2 pe-4 text-right font-medium">COGS</th>
                        <th class="py-2 pe-4 text-right font-medium">Gross profit</th>
                        <th class="py-2 text-right font-medium">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byLocation as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pe-4 text-gray-950 dark:text-white">{{ $row['location']?->name ?? 'Unassigned' }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-gray-950 dark:text-white">{{ tenant('currency')?->value }} {{ number_format($row['revenue'], 2) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-gray-950 dark:text-white">-{{ tenant('currency')?->value }} {{ number_format($row['cogs'], 2) }}</td>
                            <td class="py-2 pe-4 text-right font-medium tabular-nums text-gray-950 dark:text-white">{{ tenant('currency')?->value }} {{ number_format($row['grossProfit'], 2) }}</td>
                            <td class="py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['grossMarginPercent'], 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
