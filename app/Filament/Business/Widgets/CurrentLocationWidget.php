<?php

namespace App\Filament\Business\Widgets;

use App\Models\Location;
use Filament\Widgets\Widget;

/**
 * A session-level "acting as [Location]" preference, set once and
 * defaulted into POS/Purchase Order/Stock Transfer forms thereafter
 * (still fully overridable per-form) — staff no longer have to re-pick
 * their branch on every single form.
 */
class CurrentLocationWidget extends Widget
{
    protected string $view = 'filament.business.widgets.current-location-widget';

    protected int|string|array $columnSpan = 1;

    public ?int $locationId = null;

    public function mount(): void
    {
        $this->locationId = session('current_location_id')
            ?? Location::query()->where('is_active', true)->orderBy('name')->first()?->id;

        if ($this->locationId) {
            session(['current_location_id' => $this->locationId]);
        }
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
