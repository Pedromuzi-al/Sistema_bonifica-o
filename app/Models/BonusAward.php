<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusAward extends Model
{
    use HasFactory;

    protected $fillable = ['idea_id', 'rule_id', 'user_id', 'score', 'award_amount', 'category', 'justification', 'approved_by_user_id', 'approved_at'];

    protected $casts = ['approved_at' => 'datetime'];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BonusRule::class, 'rule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
