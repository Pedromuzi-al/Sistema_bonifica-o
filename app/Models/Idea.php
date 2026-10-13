<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Idea extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_TRIAGE = 'triage';

    public const STATUS_EVALUATION = 'evaluation';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IMPLEMENTATION = 'implementation';

    public const STATUS_IMPLEMENTED = 'implemented';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'idea_code',
        'author_id',
        'origin_sector_id',
        'title',
        'problem_description',
        'solution_description',
        'expected_results',
        'resources_needed',
        'additional_information',
        'status',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $idea) {
            $idea->idea_code ??= 'IDE-'.strtoupper(Str::random(8));
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function originSector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'origin_sector_id');
    }

    public function beneficiarySectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'idea_beneficiary_sectors');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(IdeaStatusHistory::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(IdeaEvaluation::class);
    }

    public function implementationPlan(): HasOne
    {
        return $this->hasOne(ImplementationPlan::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(IdeaResult::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function bonusAwards(): HasMany
    {
        return $this->hasMany(BonusAward::class);
    }

    public function visibleAuthorName(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user && $user->can('viewAuthorIdentity', $this)) {
            return $this->author?->name ?? 'Autor oculto';
        }

        return 'Autor confidencial';
    }
}
