<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteScore extends Model
{
    use HasFactory;

    protected $fillable = ['vote_id', 'criterion_id', 'score'];

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(VotingCriterion::class, 'criterion_id');
    }
}
