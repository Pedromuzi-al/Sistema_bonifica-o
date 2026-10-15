<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleting(fn () => throw new \LogicException('Exclusão definitiva requer procedimento de retenção autorizado.'));
    }

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
            'access_version' => 'integer',
            'blocked_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public const ROLE_LABELS = ['employee' => 'Funcionário', 'evaluator' => 'Equipe de avaliação', 'admin' => 'Administrador'];

    public const PERMISSION_LABELS = [
        'create_ideas' => 'Enviar ideias', 'vote_ideas' => 'Participar das votações',
        'evaluate_ideas' => 'Avaliar ideias e registrar resultados', 'manage_admin' => 'Administrar pessoas e organização',
        'view_author_identity' => 'Consultar a identidade dos autores', 'view_audit_logs' => 'Consultar auditoria',
        'view_public_ideas' => 'Consultar ideias publicadas',
    ];

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    public function isAvailable(): bool
    {
        return ! $this->trashed() && $this->blocked_at === null;
    }

    public function hasAdministrativeAccess(): bool
    {
        return $this->isAvailable() && $this->hasVerifiedEmail()
            && ($this->hasRole('admin') || $this->hasSystemPermission('manage_admin'));
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

    public function canViewPublicIdeas(): bool
    {
        return $this->hasRole(['employee', 'evaluator', 'admin']) || $this->hasSystemPermission('view_public_ideas');
    }

    public function hasSystemPermission(string $permission): bool
    {
        return $this->getAllPermissions()->contains('name', $permission);
    }
}
