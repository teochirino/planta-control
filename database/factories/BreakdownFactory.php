<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BreakdownFactory extends Factory
{
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $endDate = $this->faker->optional(0.7)->dateTimeBetween($startDate, '+2 days');
        
        return [
            'id_machine' => Machine::factory(),
            'id_user' => User::factory(),
            'reason' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'minutes' => $endDate ? $startDate->diffInMinutes($endDate) : null,
            'created_at' => $startDate,
            'updated_at' => now(),
        ];
    }
    
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'end_date' => null,
                'minutes' => null,
            ];
        });
    }
}