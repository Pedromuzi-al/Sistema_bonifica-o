<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'sector_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function ideas()
    {
        return $this->hasMany(Idea::class, 'author_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
