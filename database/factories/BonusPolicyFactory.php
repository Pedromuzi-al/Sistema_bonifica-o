<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BonusPolicyFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->sentence(3), 'description' => 'Bonificação sujeita à seleção, aprovação e divulgação.', 'reward_amount' => 0, 'minimum_score' => 0, 'allow_abstention' => false, 'anomaly_threshold' => 5, 'active' => true];
    }
}
