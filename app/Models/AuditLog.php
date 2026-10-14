<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = ['causer_id', 'model_type', 'model_id', 'action', 'details'];

    protected $casts = [
        'details' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('A auditoria é imutável.'));
        static::deleting(fn () => throw new \LogicException('A auditoria é imutável.'));
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
