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
            'name' => fake()->unique()->randomElement([
                'Electronics', 'Groceries & FMCG', 'Pharmaceuticals',
                'Household Supplies', 'Beverages', 'Personal Care',
            ]),
            'is_active' => true,
        ];
    }
}
