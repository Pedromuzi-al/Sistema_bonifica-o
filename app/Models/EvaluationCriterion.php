<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'weight', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];
}
