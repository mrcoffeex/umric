<?php

namespace App\Models;

use Database\Factories\PanelDefenseEvaluationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @use HasFactory<PanelDefenseEvaluationFactory>
 */
class PanelDefenseEvaluation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'panel_defense_id',
        'evaluator_id',
        'line_items',
        'final_score',
        'comments',
        'sdg_ids',
    ];

    /**
     * @return array{line_items: 'array<int, array<string, mixed>>', final_score: 'integer', sdg_ids: 'array<int, string>|null'}
     */
    protected function casts(): array
    {
        return [
            'line_items' => 'array',
            'final_score' => 'integer',
            'sdg_ids' => 'array',
        ];
    }

    public function panelDefense(): BelongsTo
    {
        return $this->belongsTo(PanelDefense::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
