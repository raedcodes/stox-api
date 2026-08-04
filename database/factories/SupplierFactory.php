<?php

namespace Database\Factories;

use App\Enums\PaymentTerms;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' '.fake()->randomElement(['Trading Co.', 'Distribution LLC', 'Wholesale Ltd.', 'Supply Group']),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('##########'),
            'address' => fake()->address(),
            'payment_terms' => fake()->randomElement(PaymentTerms::cases()),
            'is_active' => true,
        ];
    }
}
