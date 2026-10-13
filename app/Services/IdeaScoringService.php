<?php

namespace App\Services;

class IdeaScoringService
{
    public function calculateWeightedScore(float $realResults, float $technical, float $votes): float
    {
        return (($realResults * 0.5) + ($technical * 0.3) + ($votes * 0.2));
    }

    public function normalize(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            return 0;
        }

        return (($value - $min) / ($max - $min)) * 100;
    }

    public function determineCategory(float $score): string
    {
        if ($score >= 85) {
            return 'Destaque';
        }

        if ($score >= 70) {
            return 'Alto impacto';
        }

        if ($score >= 50) {
            return 'Impacto médio';
        }

        return 'Em revisão';
    }
}
