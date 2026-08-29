<?php

namespace App\Models;

use Database\Factories\WorkflowVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['version', 'is_current', 'created_by'])]
class WorkflowVersion extends Model
{
    /** @use HasFactory<WorkflowVersionFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_current' => false,
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function papers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class);
    }

    /**
     * @return list<string>
     */
    public function stepKeys(): array
    {
        return $this->steps->pluck('key')->values()->all();
    }

    /**
     * @return array<string, string>
     */
    public function stepLabels(): array
    {
        return $this->steps->pluck('label', 'key')->all();
    }
}
