<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PanelDefense extends Model
{
    use HasFactory, HasUlids;

    public const DEFENSE_TYPES = [
        'title' => 'Title Evaluation',
        'outline' => 'Outline Defense',
        'final' => 'Final Defense',
    ];

    protected $fillable = [
        'research_paper_id',
        'evaluation_format_id',
        'defense_type',
        'panel_members',
        'schedule',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'panel_members' => 'array',
        'schedule' => 'datetime',
    ];

    public function researchPaper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class);
    }

    public function evaluationFormat(): BelongsTo
    {
        return $this->belongsTo(EvaluationFormat::class, 'evaluation_format_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(PanelDefenseEvaluation::class);
    }

    public function includesPanelMember(User $user): bool
    {
        if ($user->name === null || $user->name === '') {
            return false;
        }

        $normalized = static fn (string $s) => mb_strtolower(trim($s), 'UTF-8');

        $needle = $normalized($user->name);

        foreach ($this->panel_members ?? [] as $member) {
            if ($needle === $normalized((string) $member)) {
                return true;
            }
        }

        return false;
    }

    public function scopeWhereUserIsOnPanel(Builder $query, User $user): Builder
    {
        if ($user->name === null || $user->name === '') {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereRaw(
            'EXISTS (SELECT 1 FROM jsonb_array_elements_text(panel_members::jsonb) AS t(name) WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)))',
            [$user->name]
        );
    }

    public function getDefenseTypeLabelAttribute(): string
    {
        return self::DEFENSE_TYPES[$this->defense_type] ?? ucfirst($this->defense_type);
    }
}
