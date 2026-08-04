<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            // Not ->unique(): that tracks used values for the whole PHP
            // process, not per call, and exhausts this small fixed list
            // after only a handful of draws across a multi-tenant run.
            'name' => fake()->randomElement([
                'Electronics', 'Groceries & FMCG', 'Pharmaceuticals',
                'Household Supplies', 'Beverages', 'Personal Care',
            ]),
            'is_active' => true,
        ];
    }
}
