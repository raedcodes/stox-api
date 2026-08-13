<?php

namespace App\Filament\Business\Widgets\Concerns;

/**
 * A human-readable "up/down vs. previous period" comparison, shared by every
 * KPI stat tile that compares a current figure against a prior one.
 */
trait HasTrendComparison
{
    protected function trendDescription(float $current, float $previous, string $label = 'previous period'): string
    {
        if ($current == $previous) {
            return __('dashboard.trend.same_as', ['label' => $label]);
        }

        if ($previous == 0.0) {
            return __('dashboard.trend.up_from_zero', ['label' => $label]);
        }

        $percent = (($current - $previous) / $previous) * 100;

        return __('dashboard.trend.vs', [
            'percent' => number_format(abs($percent), 1),
            'direction' => $percent >= 0 ? __('dashboard.trend.up') : __('dashboard.trend.down'),
            'label' => $label,
        ]);
    }

    protected function trendIcon(float $current, float $previous): string
    {
        return $current >= $previous ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function trendColor(float $current, float $previous): string
    {
        return $current >= $previous ? 'success' : 'danger';
    }
}
