<?php

namespace Database\Factories;

use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBatch>
 */
class ProductBatchFactory extends Factory
{
    protected $model = ProductBatch::class;

    public function definition(): array
    {
        return [
            'batch_number' => 'BATCH-'.fake()->unique()->numerify('######'),
            'quantity' => fake()->numberBetween(100, 400),
            'manufactured_date' => fake()->dateTimeBetween('-6 months', '-1 month'),
            'expiry_date' => null,
            'is_active' => true,
        ];
    }

    /**
     * Expires within the next 0-30 days — the urgent FEFO clearance window.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'quantity' => fake()->numberBetween(10, 60),
            'expiry_date' => now()->addDays(fake()->numberBetween(3, 29)),
        ]);
    }

    /**
     * Expires in 31-60 days — the "plan ahead" window.
     */
    public function expiringSoonish(): static
    {
        return $this->state(fn () => [
            'quantity' => fake()->numberBetween(30, 120),
            'expiry_date' => now()->addDays(fake()->numberBetween(31, 60)),
        ]);
    }

    /**
     * Long-dated stock, over a year out — plenty of runway.
     */
    public function longDated(): static
    {
        return $this->state(fn () => [
            'expiry_date' => now()->addDays(fake()->numberBetween(400, 720)),
        ]);
    }

    /**
     * Already expired — should surface in "expired" alerts, not be sellable.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'quantity' => fake()->numberBetween(5, 40),
            'expiry_date' => now()->subDays(fake()->numberBetween(1, 20)),
        ]);
    }

    /**
     * No expiry at all (electronics, household goods, etc.).
     */
    public function noExpiry(): static
    {
        return $this->state(fn () => [
            'expiry_date' => null,
        ]);
    }
}
