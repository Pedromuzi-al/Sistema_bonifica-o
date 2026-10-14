<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class IdeaScoringService
{
    public function calculateWeightedScore(float $realResults, float $technical, float $votes, array $weights): float
    {
        $this->validateWeights($weights);
        foreach ([$realResults, $technical, $votes] as $score) {
            if ($score < 0 || $score > 100 || ! is_finite($score)) {
                throw ValidationException::withMessages(['score' => 'As dimensões devem estar entre 0 e 100.']);
            }
        }

        return round(($realResults * $weights['real_results'] + $technical * $weights['technical'] + $votes * $weights['votes']) / 100, 2);
    }

    public function validateWeights(array $weights): void
    {
        if (count($weights) !== 3 || array_diff(['real_results', 'technical', 'votes'], array_keys($weights)) || abs(array_sum($weights) - 100) > 0.001 || min($weights) < 0 || max($weights) > 100) {
            throw ValidationException::withMessages(['weights' => 'Os três pesos devem somar 100%.']);
        }
    }

    public function normalize(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            throw ValidationException::withMessages(['score' => 'A escala de normalização é inválida.']);
        }

        return round(max(0, min(100, ($value - $min) / ($max - $min) * 100)), 2);
    }

    public function resultsScore(array $measurements, array $rubrics): ?float
    {
        $scores = [];
        foreach ($measurements as $measurement) {
            foreach ($rubrics as $rubric) {
                if ($rubric['indicator'] !== $measurement['indicator'] || $rubric['unit'] !== $measurement['unit']) {
                    continue;
                }
                $before = $measurement['before'] ?? null;
                $after = $measurement['after'] ?? null;
                if ($after === null || ($rubric['direction'] !== 'qualitative' && $before === null)) {
                    continue;
                }
                $gain = match ($rubric['direction']) {
                    'decrease' => (float) $before - (float) $after,
                    'increase' => (float) $after - (float) $before,
                    'qualitative' => (float) $after,
                };
                $scores[] = $this->normalize($gain, 0, (float) $rubric['target']);
                break;
            }
        }

        return $scores ? round(array_sum($scores) / count($scores), 2) : null;
    }
}
