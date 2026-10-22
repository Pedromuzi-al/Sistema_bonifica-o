<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalModel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'criteria', 'active'];

    protected $casts = ['criteria' => 'array', 'active' => 'boolean'];
}
