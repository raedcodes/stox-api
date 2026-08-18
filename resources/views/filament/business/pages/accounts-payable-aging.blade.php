<x-filament-panels::page>
    @php $rows = $this->rows(); $totals = $this->grandTotals(); $currency = tenant('currency')?->value; @endphp

    @include('filament.business.components.ai-summary-card')

    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pe-4 text-left font-medium">{{ __('common.fields.supplier') }}</th>
                        <th class="py-2 pe-4 text-right font-medium">{{ __('accounts_payable_aging.buckets.current') }}</th>
                        <th class="py-2 pe-4 text-right font-medium">{{ __('accounts_payable_aging.buckets.1_30') }}</th>
                        <th class="py-2 pe-4 text-right font-medium">{{ __('accounts_payable_aging.buckets.31_60') }}</th>
                        <th class="py-2 pe-4 text-right font-medium">{{ __('accounts_payable_aging.buckets.61_90') }}</th>
                        <th class="py-2 pe-4 text-right font-medium">{{ __('accounts_payable_aging.buckets.over_90') }}</th>
                        <th class="py-2 text-right font-medium">{{ __('common.fields.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pe-4 text-gray-950 dark:text-white">{{ $row['supplier']->name }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($row['buckets']['current'], 2) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($row['buckets']['1_30'], 2) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($row['buckets']['31_60'], 2) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-warning-600 dark:text-warning-400">{{ $currency }} {{ number_format($row['buckets']['61_90'], 2) }}</td>
                            <td class="py-2 pe-4 text-right tabular-nums text-danger-600 dark:text-danger-400">{{ $currency }} {{ number_format($row['buckets']['over_90'], 2) }}</td>
                            <td class="py-2 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($row['buckets']['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-500 dark:text-gray-400">{{ __('accounts_payable_aging.empty_state') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-gray-950 dark:border-white">
                            <td class="py-2.5 pe-4 font-semibold text-gray-950 dark:text-white">{{ __('common.fields.total') }}</td>
                            <td class="py-2.5 pe-4 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($totals['current'], 2) }}</td>
                            <td class="py-2.5 pe-4 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($totals['1_30'], 2) }}</td>
                            <td class="py-2.5 pe-4 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($totals['31_60'], 2) }}</td>
                            <td class="py-2.5 pe-4 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($totals['61_90'], 2) }}</td>
                            <td class="py-2.5 pe-4 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($totals['over_90'], 2) }}</td>
                            <td class="py-2.5 text-right font-semibold tabular-nums text-gray-950 dark:text-white">{{ $currency }} {{ number_format($totals['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>
