<?php

namespace App\Models;

use App\Support\EvaluationPdfSettings;
use Database\Factories\EvaluationFormatFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named rubric used when scheduling a panel defense.
 * Scoring (no weights): each criterion is scored 1–100; the evaluation total is the average.
 * Scoring (use_weights): each criterion has a % weight; total = sum(score/100 * weight). Checklist: yes/no (max_points 1 each).
 *
 * @use HasFactory<EvaluationFormatFactory>
 */
class EvaluationFormat extends Model
{
    use HasFactory, HasUlids;

    public const TYPE_SCORING = 'scoring';

    public const TYPE_CHECKLIST = 'checklist';

    /** @var list<string> */
    public const TYPES = [self::TYPE_SCORING, self::TYPE_CHECKLIST];

    protected $fillable = [
        'name',
        'evaluation_type',
        'use_weights',
        'pdf_settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_weights' => 'boolean',
            'pdf_settings' => 'array',
        ];
    }

    public function isPdfExportEnabled(): bool
    {
        return EvaluationPdfSettings::isEnabledForFormat($this);
    }

    public function isScoring(): bool
    {
        return $this->evaluation_type === self::TYPE_SCORING;
    }

    public function isChecklist(): bool
    {
        return $this->evaluation_type === self::TYPE_CHECKLIST;
    }

    /** When true, each criterion’s max_points is its % weight (must sum to 100). */
    public function scoringUsesWeights(): bool
    {
        return $this->isScoring() && (bool) $this->use_weights;
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(EvaluationCriterion::class, 'evaluation_format_id');
    }

    public function panelDefenses(): HasMany
    {
        return $this->hasMany(PanelDefense::class, 'evaluation_format_id');
    }

    public function totalMax(): int
    {
        return (int) $this->criteria()->sum('max_points');
    }

    public function isReady(): bool
    {
        if (! $this->criteria()->exists()) {
            return false;
        }

        if ($this->isChecklist()) {
            return true;
        }

        if ($this->scoringUsesWeights()) {
            return $this->totalMax() === EvaluationCriterion::MAX_TOTAL;
        }

        // Unweighted scoring: at least one criterion; each row stores max_points 100 in DB.
        return true;
    }
}
