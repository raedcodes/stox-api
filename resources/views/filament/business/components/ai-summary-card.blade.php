{{--
    Shared AI summary card for report pages using the HasStructuredAiSummary
    trait. Expects, in scope (all page properties/methods, so a plain
    @include from the parent view already has them available):
    - $aiSummary (array|null: executive_summary, key_insights[]: {highlight,
      detail}, warnings[]: {metric, value, text, severity}, recommendations[],
      generated_at)
    - $aiSummaryError (string|null)
    - $this->aiSummaryKpis(): array<{label, value}> — deterministic figures
      computed in PHP, shown regardless of whether an AI summary exists yet.

    The loading indicator is scoped to exactly this page's "generateAiSummary"
    action (not "any action on the page") via Livewire's method+params
    wire:target matching — confirmed against Filament's actual generated
    wire:click value for a zero-argument page action.
--}}
@php $aiSummaryKpis = $this->aiSummaryKpis(); @endphp
@if (! empty($aiSummaryKpis))
    <div class="fi-section grid grid-cols-1 gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 sm:grid-cols-3 dark:bg-gray-900 dark:ring-white/10">
        @foreach ($aiSummaryKpis as $kpi)
            <div class="{{ ! $loop->last ? 'sm:border-e sm:border-gray-100 sm:dark:border-white/5' : '' }} sm:pe-3">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</p>
                <p class="mt-0.5 text-xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>
@endif

<div wire:loading.flex wire:target="mountAction('generateAiSummary')" class="fi-section items-center gap-3 rounded-xl border-s-4 border-indigo-300 bg-indigo-50/40 p-4 dark:border-indigo-500/50 dark:bg-indigo-500/5">
    <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 flex-shrink-0 animate-spin text-indigo-500" />
    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('ai_summary.generating') }}</p>
</div>

<div wire:loading.remove wire:target="mountAction('generateAiSummary')">
    @if ($aiSummaryError)
        <div class="fi-section flex items-start gap-3 rounded-xl border-s-4 border-danger-400 bg-danger-50/60 p-4 dark:border-danger-500 dark:bg-danger-500/10">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-4 w-4 flex-shrink-0 text-danger-500" />
            <div>
                <p class="text-sm font-medium text-danger-700 dark:text-danger-400">{{ __('ai_summary.error_title') }}</p>
                <p class="mt-0.5 text-xs text-danger-600/80 dark:text-danger-400/70">{{ $aiSummaryError }}</p>
            </div>
        </div>
    @elseif ($aiSummary)
        <div class="fi-section rounded-xl border-s-4 border-indigo-400 bg-indigo-50/60 p-6 shadow-sm ring-1 ring-gray-950/5 dark:border-indigo-500 dark:bg-indigo-500/10 dark:ring-white/10">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                <h3 class="flex items-center gap-1.5 text-sm font-semibold text-gray-950 dark:text-white">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4 text-indigo-500" />
                    {{ __('ai_summary.title') }}
                </h3>

                @if ($aiSummary['generated_at'] ?? null)
                    <span class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                        <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5" />
                        {{ __('ai_summary.generated', ['time' => \Illuminate\Support\Carbon::parse($aiSummary['generated_at'])->diffForHumans()]) }}
                    </span>
                @endif
            </div>

            @if (! empty($aiSummary['executive_summary']))
                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $aiSummary['executive_summary'] }}</p>
            @endif

            @if (! empty($aiSummary['key_insights']))
                <div class="mt-4">
                    <h4 class="mb-1.5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-light-bulb" class="h-3.5 w-3.5" />
                        {{ __('ai_summary.key_insights') }}
                    </h4>
                    <ul class="space-y-1.5">
                        @foreach ($aiSummary['key_insights'] as $insight)
                            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-indigo-400"></span>
                                <span>
                                    @if (! empty($insight['highlight']))
                                        <strong class="font-semibold text-gray-950 dark:text-white">{{ $insight['highlight'] }}</strong>
                                        @if (! empty($insight['detail']))
                                            &nbsp;&mdash;&nbsp;{{ $insight['detail'] }}
                                        @endif
                                    @else
                                        {{ $insight['detail'] ?? '' }}
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($aiSummary['warnings']))
                <div class="mt-4">
                    <h4 class="mb-1.5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-3.5 w-3.5" />
                        {{ __('ai_summary.attention_needed') }}
                    </h4>
                    <ul class="space-y-1.5">
                        @foreach ($aiSummary['warnings'] as $warning)
                            @php
                                $severityDot = match ($warning['severity'] ?? 'low') {
                                    'high' => 'bg-danger-500',
                                    'medium' => 'bg-warning-400',
                                    default => 'bg-gray-400',
                                };
                            @endphp
                            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full {{ $severityDot }}"></span>
                                <span>
                                    @if (! empty($warning['metric']))
                                        <strong class="font-semibold text-gray-950 dark:text-white">{{ $warning['metric'] }}@if (! empty($warning['value'])): {{ $warning['value'] }}@endif</strong>
                                        @if (! empty($warning['text']))
                                            &nbsp;&mdash;&nbsp;{{ $warning['text'] }}
                                        @endif
                                    @else
                                        {{ $warning['text'] ?? '' }}
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($aiSummary['recommendations']))
                <div class="mt-4">
                    <h4 class="mb-1.5 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-check-circle" class="h-3.5 w-3.5" />
                        {{ __('ai_summary.recommendations') }}
                    </h4>
                    <ul class="space-y-1.5">
                        @foreach ($aiSummary['recommendations'] as $recommendation)
                            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-success-400"></span>
                                <span>{{ $recommendation }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @else
        <div class="fi-section flex flex-wrap items-center gap-3 rounded-xl border border-dashed border-gray-300 p-4 dark:border-white/10">
            <x-filament::icon icon="heroicon-o-sparkles" class="h-4 w-4 flex-shrink-0 text-gray-400 dark:text-gray-500" />
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ai_summary.empty_state') }}</p>
        </div>
    @endif
</div>
