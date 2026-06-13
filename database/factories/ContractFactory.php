<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'end_date' => null,
            'status' => ContractStatus::Active,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Cancelled,
        ]);
    }
}
