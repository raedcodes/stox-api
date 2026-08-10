<?php

namespace App\Filament\Business\Widgets;

use App\Models\Location;
use Filament\Widgets\Widget;

/**
 * A session-level "acting as [Location]" preference, set once and
 * defaulted into POS/Purchase Order/Stock Transfer/Stock Adjustment forms
 * thereafter (still fully overridable per-form) — staff no longer have to
 * re-pick their branch on every single form. Also scopes the dashboard's
 * own widgets (DashboardKpisWidget, LowStockWidget, ExpiryAlertsWidget) to
 * this location. Null means "All Locations" — an explicit option, not
 * just an empty state, so an Owner can pull back to the combined view.
 */
class CurrentLocationWidget extends Widget
{
    protected string $view = 'filament.business.widgets.current-location-widget';

    protected int|string|array $columnSpan = 1;

    public ?int $locationId = null;

    public function mount(): void
    {
        // ->has() treats a null value as "not set" (isset() semantics) —
        // wrong here, since null is the deliberate "All Locations" choice,
        // not an absent one. ->exists() checks true key presence instead.
        $this->locationId = session()->exists('current_location_id')
            ? session('current_location_id')
            : Location::query()->where('is_active', true)->orderBy('name')->first()?->id;

        session(['current_location_id' => $this->locationId]);
    }

    public function updatedLocationId(): void
    {
        session(['current_location_id' => $this->locationId]);
    }

    public function getLocations()
    {
        return Location::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id');
    }
}
