<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            // Not ->unique(): that tracks used values for the whole PHP
            // process, not per call, and exhausts this small fixed list
            // after only a handful of draws across a multi-tenant run.
            'name' => fake()->randomElement([
                'Nova', 'Meridian', 'Everstone', 'BluePeak', 'Solace',
                'Cascade', 'Northline', 'Amberly', 'Vantage', 'Pure Origin',
            ]),
            'is_active' => true,
        ];
    }
}
