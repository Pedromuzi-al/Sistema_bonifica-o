<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

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
