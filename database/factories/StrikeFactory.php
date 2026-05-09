<?php

namespace Database\Factories;

use App\Models\ProductionLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class StrikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_production_lines' => ProductionLine::factory(),
            'description' => $this->faker->sentence(),
            'minutes' => $this->faker->numberBetween(5, 120),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }
}