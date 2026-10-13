<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaEvaluationScore extends Model
{
    use HasFactory;

    protected $fillable = ['evaluation_id', 'criterion_id', 'score', 'notes'];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(IdeaEvaluation::class, 'evaluation_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criterion_id');
    }
}
