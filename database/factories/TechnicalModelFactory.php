<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TechnicalModelFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->sentence(3), 'description' => 'Avaliação do benefício esperado.', 'active' => true, 'criteria' => [
            ['name' => 'Utilidade', 'description' => 'Quanto esta ideia pode ajudar no trabalho?', 'weight' => 34],
            ['name' => 'Relevância', 'description' => 'Qual a importância do problema que esta ideia resolve?', 'weight' => 33],
            ['name' => 'Benefício esperado', 'description' => 'Qual o potencial de melhoria apresentado pela proposta?', 'weight' => 33],
        ]];
    }
}
