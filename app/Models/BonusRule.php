<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonusRule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'minimum_score', 'max_awards', 'reward_amount', 'eligible_status', 'criteria', 'active'];

    protected $casts = ['criteria' => 'array', 'active' => 'boolean'];

    public function awards(): HasMany
    {
        return $this->hasMany(BonusAward::class, 'rule_id');
    }
}
