<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function ideasAsOrigin()
    {
        return $this->hasMany(Idea::class, 'origin_sector_id');
    }

    public function ideasAsBeneficiary()
    {
        return $this->belongsToMany(Idea::class, 'idea_beneficiary_sectors');
    }
}
