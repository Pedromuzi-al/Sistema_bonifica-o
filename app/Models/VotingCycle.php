<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingCycle extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'starts_at', 'ends_at', 'status', 'weight_configuration', 'frozen_configuration', 'closed_at'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'weight_configuration' => 'array', 'frozen_configuration' => 'array', 'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $cycle) {
            if ($cycle->getOriginal('frozen_configuration') && $cycle->isDirty(['name', 'starts_at', 'ends_at', 'weight_configuration', 'frozen_configuration'])) {
                throw new \LogicException('As regras de um ciclo aberto são imutáveis.');
            }
        });
    }

    protected function localStartsAt(): Attribute
    {
        return Attribute::get(fn () => $this->starts_at?->copy()->setTimezone(config('innovation.cycle_timezone')));
    }

    protected function localEndsAt(): Attribute
    {
        return Attribute::get(fn () => $this->ends_at?->copy()->setTimezone(config('innovation.cycle_timezone')));
    }

    public function isSelection(): bool
    {
        return ($this->frozen_configuration['purpose'] ?? $this->weight_configuration['purpose'] ?? 'recognition') === 'selection';
    }

    public function ideas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_voting_cycle')->withPivot('snapshot', 'outcome');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(VotingCriterion::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
