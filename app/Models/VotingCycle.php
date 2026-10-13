<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingCycle extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'starts_at', 'ends_at', 'status', 'weight_configuration'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'weight_configuration' => 'array',
    ];

    public function criteria(): HasMany
    {
        return $this->hasMany(VotingCriterion::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
