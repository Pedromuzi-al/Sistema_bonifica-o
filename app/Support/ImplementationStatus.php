<?php

namespace App\Support;

use App\Models\Idea;

class ImplementationStatus
{
    public const LABELS = [
        'planned' => 'Ainda será implementada',
        'in_progress' => 'Em implementação',
        'implemented' => 'Implementada',
    ];

    public static function forIdea(Idea $idea): string
    {
        return match ($idea->status) {
            'implementation' => 'in_progress',
            'implemented', 'validation', 'completed' => 'implemented',
            default => $idea->implementationPlan?->completed_at || $idea->result?->validated_at ? 'implemented' : 'planned',
        };
    }

    public static function fromSnapshot(array $snapshot): string
    {
        $status = $snapshot['implementation_status'] ?? $snapshot['publication']['implementation_status'] ?? null;
        if (is_string($status) && array_key_exists($status, self::LABELS)) {
            return $status;
        }

        // Older recognition cycles only admitted implemented ideas and froze a
        // real-results score. Read that evidence without consulting mutable data.
        return isset($snapshot['real_results']) || ! empty($snapshot['publication']['implementation_completed_at'])
            ? 'implemented' : 'planned';
    }
}
