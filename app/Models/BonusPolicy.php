<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusPolicy extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'reward_amount', 'minimum_score', 'allow_abstention', 'anomaly_threshold', 'active'];

    protected $casts = ['reward_amount' => 'decimal:2', 'minimum_score' => 'decimal:2', 'allow_abstention' => 'boolean', 'anomaly_threshold' => 'integer', 'active' => 'boolean'];
}
