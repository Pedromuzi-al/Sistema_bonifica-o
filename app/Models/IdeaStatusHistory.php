<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = ['idea_id', 'from_status', 'to_status', 'changed_by_user_id', 'notes'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('O histórico é imutável.'));
        static::deleting(fn () => throw new \LogicException('O histórico é imutável.'));
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public static function record(Idea $idea, string $toStatus, ?User $user = null, ?string $notes = null): self
    {
        $record = self::create([
            'idea_id' => $idea->id,
            'from_status' => $idea->status,
            'to_status' => $toStatus,
            'changed_by_user_id' => $user?->id,
            'notes' => $notes,
        ]);

        $idea->status = $toStatus;
        $idea->save();

        return $record;
    }
}
