<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{ $this->form }}
    </div>

    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-left text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                    <th class="px-3 py-2 font-semibold">Date</th>
                    <th class="px-3 py-2 font-semibold">Type</th>
                    <th class="px-3 py-2 font-semibold">Description</th>
                    <th class="px-3 py-2 text-right font-semibold">Amount</th>
                    <th class="px-3 py-2 text-right font-semibold">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-200 italic text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <td class="px-3 py-2.5" colspan="4">Opening balance</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">{{ tenant('currency')?->value }} {{ number_format($this->openingBalance(), 2) }}</td>
                </tr>
                @forelse ($this->rows() as $row)
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <td class="px-3 py-2.5">{{ $row['transaction']->transaction_date->format('Y-m-d') }}</td>
                        <td class="px-3 py-2.5">{{ $row['transaction']->type->getLabel() }}</td>
                        <td class="px-3 py-2.5">{{ $row['transaction']->description ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ tenant('currency')?->value }} {{ number_format($row['transaction']->amount, 2) }}</td>
                        <td class="px-3 py-2.5 text-right tabular-nums">{{ tenant('currency')?->value }} {{ number_format($row['running_balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-4 text-gray-400" colspan="5">Select a supplier to see their statement.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4 flex justify-end border-t-2 border-gray-950 pt-2 font-semibold tabular-nums text-gray-950 dark:border-white dark:text-white">
            Closing balance: {{ tenant('currency')?->value }} {{ number_format($this->closingBalance(), 2) }}
        </div>
    </div>
</x-filament-panels::page>
