<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaResult extends Model
{
    use HasFactory;

    protected $fillable = ['idea_id', 'time_saved_hours', 'errors_reduced', 'cost_savings', 'impact_summary', 'validated_by_user_id', 'measured_at', 'measurements', 'limitations', 'validated_at'];

    protected $casts = [
        'measured_at' => 'datetime', 'measurements' => 'array', 'validated_at' => 'datetime',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }
}
