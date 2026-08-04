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
            'name' => fake()->unique()->randomElement([
                'Nova', 'Meridian', 'Everstone', 'BluePeak', 'Solace',
                'Cascade', 'Northline', 'Amberly', 'Vantage', 'Pure Origin',
            ]),
            'is_active' => true,
        ];
    }
}
