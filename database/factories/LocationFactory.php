<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Main Warehouse', 'Downtown Storefront', 'Uptown Storefront',
                'Distribution Center', 'Airport Road Branch',
            ]),
            'type' => fake()->randomElement(LocationType::cases()),
            'is_active' => true,
        ];
    }

    public function warehouse(): static
    {
        return $this->state(fn () => ['type' => LocationType::Warehouse]);
    }

    public function store(): static
    {
        return $this->state(fn () => ['type' => LocationType::Store]);
    }
}
