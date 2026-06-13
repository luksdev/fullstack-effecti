<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'service_id' => Service::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'unit_price' => fake()->numberBetween(1000, 100000),
        ];
    }
}
