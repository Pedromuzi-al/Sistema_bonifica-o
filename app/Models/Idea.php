<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
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

    public const STATUS_AWAITING_INFORMATION = 'awaiting_information';

    public const STATUS_VALIDATION = 'validation';

    public const STATUS_VOTING = 'voting';

    public const STATUS_VOTING_CLOSED = 'voting_closed';

    public const STATUS_FINALIZED = 'finalized';

    public const LABELS = [
        'idea_bank' => 'No banco de ideias', 'submitted' => 'Enviada', 'triage' => 'Em triagem', 'evaluation' => 'Em avaliação',
        'awaiting_information' => 'Aguardando informações', 'approved' => 'Aprovada',
        'implementation' => 'Em implementação', 'implemented' => 'Implementada (legado)',
        'validation' => 'Em validação de resultados', 'completed' => 'Concluída',
        'voting' => 'Disponível para votação', 'voting_closed' => 'Votação encerrada',
        'finalized' => 'Finalizada', 'rejected' => 'Rejeitada',
    ];

    protected $hidden = ['author_id', 'author', 'published_by_user_id', 'contributors'];

    protected $fillable = [
        'publication', 'published_by_user_id', 'published_at',
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
        'publication' => 'array', 'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Ideias devem ser preservadas pelo procedimento de retenção.'));
        static::creating(function (self $idea) {
            $idea->idea_code ??= 'IDE-'.strtoupper(Str::random(8));
        });
    }

    public function isContributor(User $user): bool
    {
        return (int) $this->author_id === (int) $user->id || ($this->relationLoaded('contributors') ? $this->contributors->contains('id', $user->id) : DB::table('idea_contributors')->where('idea_id', $this->id)->where('user_id', $user->id)->exists());
    }

    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_contributors')->withTrashed()->withPivot('share');
    }

    public function recognitionShares(): array
    {
        $shares = DB::table('idea_contributors')->where('idea_id', $this->id)->orderBy('user_id')->pluck('share', 'user_id')->all();

        return $shares ?: [$this->author_id => 100];
    }

    public function hasConflict(User $user): bool
    {
        return $this->isContributor($user)
            || DB::table('idea_conflicts')->where('idea_id', $this->id)->where('user_id', $user->id)->exists();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
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

    public function canRecordTechnicalEvaluation(): bool
    {
        return $this->status === 'evaluation'
            || (in_array($this->status, ['approved', 'implementation', 'validation', 'completed'], true)
                && DB::table('cycle_idea_decisions')->where('idea_id', $this->id)->where('decision', 'selected')->exists());
    }

    public function votingCycles(): BelongsToMany
    {
        return $this->belongsToMany(VotingCycle::class, 'idea_voting_cycle')->withPivot('snapshot', 'outcome');
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
