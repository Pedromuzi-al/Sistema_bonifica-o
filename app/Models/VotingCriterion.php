<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotingCriterion extends Model
{
    use HasFactory;

    protected $fillable = ['voting_cycle_id', 'name', 'weight', 'description'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(VotingCycle::class, 'voting_cycle_id');
    }
}
