<?php

namespace App\Models;

use App\Services\WorkflowCatalog;
use App\Support\WorkflowStepConfig;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ResearchPaper extends Model
{
    use HasFactory, HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'school_class_id',
        'adviser_id',
        'statistician_id',
        'category_id',
        'sdg_ids',
        'agenda_ids',
        'title',
        'abstract',
        'proponents',
        'tracking_id',
        'status',
        'workflow_version_id',
        'current_step',
        'custom_step_statuses',
        'step_input_values',
        'step_ric_review',
        'step_plagiarism',
        'plagiarism_attempts',
        'plagiarism_score',
        'step_outline_defense',
        'outline_defense_schedule',
        'step_data_gathering',
        'step_rating',
        'grade',
        'step_final_manuscript',
        'step_final_defense',
        'final_defense_schedule',
        'step_hard_bound',
        'submission_date',
        'publication_date',
        'keywords',
        'views',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'publication_date' => 'date',
        'outline_defense_schedule' => 'datetime',
        'final_defense_schedule' => 'datetime',
        'proponents' => 'array',
        'sdg_ids' => 'array',
        'agenda_ids' => 'array',
        'views' => 'integer',
        'plagiarism_attempts' => 'integer',
        'grade' => 'decimal:2',
        'plagiarism_score' => 'decimal:2',
        'custom_step_statuses' => 'array',
        'step_input_values' => 'array',
    ];

    // Workflow constants
    public const STEPS = [
        'title_proposal',
        'ric_review',
        'outline_defense',
        'data_gathering',
        'rating',
        'final_manuscript',
        'final_defense',
        'hard_bound',
        'completed',
    ];

    public const STEP_LABELS = [
        'title_proposal' => 'Title Evaluation',
        'ric_review' => 'RIC/Admin Review',
        'outline_defense' => 'Outline Defense',
        'data_gathering' => 'Data Gathering',
        'rating' => 'Rating',
        'final_manuscript' => 'Final Manuscript',
        'final_defense' => 'Final Defense',
        'hard_bound' => 'Hard Bound',
        'completed' => 'Completed',
    ];

    public const MAX_PLAGIARISM_ATTEMPTS = 3;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($paper) {
            if (empty($paper->tracking_id)) {
                $paper->tracking_id = 'RP-'.strtoupper(Str::random(8));
            }
            if (empty($paper->submission_date)) {
                $paper->submission_date = now();
            }
            if (empty($paper->workflow_version_id)) {
                $catalog = app(WorkflowCatalog::class);
                $current = $catalog->current();
                $paper->workflow_version_id = $current->id;
                if (empty($paper->current_step)) {
                    $paper->current_step = $current->stepKeys()[0] ?? 'title_proposal';
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function statistician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'statistician_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'paper_authors')
            ->withPivot('author_order')
            ->orderBy('pivot_author_order');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PaperFile::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function publication(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function trackingRecords(): HasMany
    {
        return $this->hasMany(TrackingRecord::class)->latest();
    }

    public function panelDefenses(): HasMany
    {
        return $this->hasMany(PanelDefense::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class);
    }

    /**
     * @return list<string>
     */
    public function stepKeys(): array
    {
        $this->loadMissing('workflowVersion.steps');

        $keys = $this->workflowVersion?->stepKeys() ?? [];

        return $keys !== [] ? $keys : self::STEPS;
    }

    /**
     * @return array<string, string>
     */
    public function stepLabels(): array
    {
        $this->loadMissing('workflowVersion.steps');

        $labels = $this->workflowVersion?->stepLabels() ?? [];

        return $labels !== [] ? $labels : self::STEP_LABELS;
    }

    public function labelForStep(string $key): string
    {
        return $this->stepLabels()[$key] ?? self::STEP_LABELS[$key] ?? $key;
    }

    public function firstStepKey(): string
    {
        return $this->stepKeys()[0] ?? 'title_proposal';
    }

    public function nextStepKey(?string $from = null): ?string
    {
        $keys = $this->stepKeys();
        $from ??= $this->current_step;
        $index = array_search($from, $keys, true);

        if ($index === false) {
            return null;
        }

        return $keys[$index + 1] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function updatesForEnteringStep(string $key): array
    {
        return match ($key) {
            'title_proposal' => [],
            'ric_review' => ['step_ric_review' => $this->step_ric_review ?? 'pending'],
            'outline_defense' => ['step_outline_defense' => $this->step_outline_defense ?? 'pending'],
            'data_gathering' => ['step_data_gathering' => $this->step_data_gathering ?? 'pending'],
            'rating' => ['step_rating' => $this->step_rating ?? 'pending'],
            'final_manuscript' => ['step_final_manuscript' => $this->step_final_manuscript ?? 'pending'],
            'final_defense' => ['step_final_defense' => $this->step_final_defense ?? 'pending'],
            'hard_bound' => ['step_hard_bound' => $this->step_hard_bound ?? 'ongoing'],
            'completed' => ['status' => 'published'],
            default => [
                'custom_step_statuses' => array_merge($this->custom_step_statuses ?? [], [
                    $key => $this->customStepStatus($key) ?? 'pending',
                ]),
            ],
        };
    }

    public function customStepStatus(string $key): ?string
    {
        $statuses = $this->custom_step_statuses ?? [];

        return isset($statuses[$key]) ? (string) $statuses[$key] : null;
    }

    /**
     * @return array{statuses: list<array{value: string, label: string, color: string, completes: bool}>, inputs: list<array{key: string, label: string, type: string, show_on_calendar: bool}>}
     */
    public function stepConfig(string $key): array
    {
        $this->loadMissing('workflowVersion.steps');

        $step = $this->workflowVersion?->steps->firstWhere('key', $key);

        return WorkflowStepConfig::normalize(
            is_array($step?->config) ? $step->config : null,
            $key,
        );
    }

    /**
     * @return array<string, array{statuses: list<array{value: string, label: string, color: string, completes: bool}>, inputs: list<array{key: string, label: string, type: string, show_on_calendar: bool}>}>
     */
    public function stepConfigs(): array
    {
        $configs = [];

        foreach ($this->stepKeys() as $key) {
            $configs[$key] = $this->stepConfig($key);
        }

        return $configs;
    }

    public function statusCompletesStep(string $step, string $status): bool
    {
        return WorkflowStepConfig::statusCompletes($this->stepConfig($step), $status);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function updatesForStepFields(string $step, array $fields): array
    {
        $updates = [];
        $stored = $this->step_input_values ?? [];
        $stepValues = is_array($stored[$step] ?? null) ? $stored[$step] : [];
        $changedCustom = false;

        foreach ($this->stepConfig($step)['inputs'] as $input) {
            $key = $input['key'];
            if (! array_key_exists($key, $fields)) {
                continue;
            }

            $value = $fields[$key];
            $normalized = $value === '' ? null : $value;

            if ($key === 'grade') {
                $updates['grade'] = $normalized;
            } elseif ($key === 'schedule' && $step === 'outline_defense') {
                $updates['outline_defense_schedule'] = $normalized;
            } elseif ($key === 'schedule' && $step === 'final_defense') {
                $updates['final_defense_schedule'] = $normalized;
            } else {
                $stepValues[$key] = $normalized;
                $changedCustom = true;
            }
        }

        if ($changedCustom) {
            $stored[$step] = $stepValues;
            $updates['step_input_values'] = $stored;
        }

        return $updates;
    }

    // Workflow helpers
    public function getStepLabelAttribute(): string
    {
        return $this->labelForStep($this->current_step);
    }

    public function getCurrentStepStatusAttribute(): ?string
    {
        return match ($this->current_step) {
            'title_proposal' => 'submitted',
            'ric_review' => $this->step_ric_review,
            'outline_defense' => $this->step_outline_defense,
            'data_gathering' => $this->step_data_gathering,
            'rating' => $this->step_rating,
            'final_manuscript' => $this->step_final_manuscript,
            'final_defense' => $this->step_final_defense,
            'hard_bound' => $this->step_hard_bound,
            default => $this->customStepStatus($this->current_step),
        };
    }

    public function isCompleted(): bool
    {
        return $this->current_step === 'completed';
    }

    public function isRicReviewReturned(): bool
    {
        return strcasecmp((string) ($this->step_ric_review ?? ''), 'returned') === 0;
    }

    /**
     * Main author (user_id) or a listed proponent, including mixed id types in JSON.
     */
    public function isOwnerOrProponent(mixed $userId): bool
    {
        if ((string) $this->user_id === (string) $userId) {
            return true;
        }

        return collect($this->proponents ?? [])
            ->contains(fn (mixed $p) => is_array($p) && isset($p['id']) && (string) $p['id'] === (string) $userId);
    }

    /**
     * Whether a student may upload an outline or final defense research file.
     * Allowed when: RIC review is returned, or the paper is on the matching defense step
     * (outline file at outline defense, final file at final defense). When RIC is returned, both apply.
     *
     * Students get one upload per allowed status: once an outline (or final) defense file
     * exists on this paper, the matching slot is considered consumed.
     *
     * @param  'outline'|'final'  $defense
     */
    public function mayStudentUploadDefenseDocument(string $defense): bool
    {
        $statusAllows = match ($defense) {
            'outline' => $this->isRicReviewReturned() || $this->current_step === 'outline_defense',
            'final' => $this->isRicReviewReturned() || $this->current_step === 'final_defense',
            default => false,
        };

        if (! $statusAllows) {
            return false;
        }

        return ! $this->hasStudentDefenseDocument($defense);
    }

    /**
     * Whether the paper already has a student-uploaded file for the given defense type.
     *
     * @param  'outline'|'final'  $defense
     */
    public function hasStudentDefenseDocument(string $defense): bool
    {
        $category = match ($defense) {
            'outline' => PaperFile::CATEGORY_OUTLINE_DEFENSE,
            'final' => PaperFile::CATEGORY_FINAL_DEFENSE,
            default => null,
        };

        if ($category === null) {
            return false;
        }

        return $this->files()
            ->where('file_category', $category)
            ->exists();
    }

    public function canProceedToNextStep(): bool
    {
        if ($this->current_step === 'title_proposal') {
            return true;
        }

        $status = $this->current_step_status;

        return is_string($status) && $this->statusCompletesStep($this->current_step, $status);
    }

    public function advanceToNextStep(): void
    {
        $next = $this->nextStepKey();
        if ($next === null) {
            return;
        }

        $this->fill($this->updatesForEnteringStep($next));
        $this->current_step = $next;
        $this->save();
    }

    // Scopes
    public function scopeForClass($query, int $classId)
    {
        return $query->where('school_class_id', $classId);
    }

    public function scopeByStep($query, string $step)
    {
        return $query->where('current_step', $step);
    }

    public function scopePendingReview($query)
    {
        return $query->where('current_step', 'ric_review')
            ->where(function ($q) {
                $q->whereNull('step_ric_review')->orWhere('step_ric_review', 'pending');
            });
    }
}
