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
            return "Same as {$label}";
        }

        if ($previous == 0.0) {
            return "Up from zero {$label}";
        }

        $percent = (($current - $previous) / $previous) * 100;

        return number_format(abs($percent), 1).'% '.($percent >= 0 ? 'up' : 'down')." vs. {$label}";
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
