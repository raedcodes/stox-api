<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $isBusiness = fake()->boolean(35);

        return [
            'name' => $isBusiness ? fake()->company() : fake()->name(),
            'customer_type' => $isBusiness ? CustomerType::B2B : CustomerType::B2C,
            'is_walk_in' => false,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }

    public function business(): static
    {
        return $this->state(fn () => [
            'name' => fake()->company(),
            'customer_type' => CustomerType::B2B,
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn () => [
            'name' => fake()->name(),
            'customer_type' => CustomerType::B2C,
        ]);
    }
}
