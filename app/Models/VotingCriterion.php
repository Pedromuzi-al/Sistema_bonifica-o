<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotingCriterion extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        $guard = function (self $criterion) {
            if (VotingCycle::find($criterion->voting_cycle_id)?->frozen_configuration || ($criterion->exists && VotingCycle::find($criterion->getOriginal('voting_cycle_id'))?->frozen_configuration)) {
                throw new \LogicException('Critérios de ciclos abertos são imutáveis.');
            }
        };
        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    protected $fillable = ['voting_cycle_id', 'name', 'weight', 'description'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(VotingCycle::class, 'voting_cycle_id');
    }
}
