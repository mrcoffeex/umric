<?php

namespace App\Models;

use App\Support\RichTextSanitizer;
use Database\Factories\EvaluationCriterionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @use HasFactory<EvaluationCriterionFactory>
 */
class EvaluationCriterion extends Model
{
    use HasFactory, HasUlids;

    public const MAX_TOTAL = 100;

    protected $fillable = [
        'evaluation_format_id',
        'name',
        'content',
        'section_heading',
        'max_points',
        'sort_order',
    ];

    public function setContentAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['content'] = null;
            $this->syncNameFromContent(null);

            return;
        }

        $this->attributes['content'] = RichTextSanitizer::sanitize($value);
        $this->syncNameFromContent($this->attributes['content']);
    }

    public function evaluationFormat(): BelongsTo
    {
        return $this->belongsTo(EvaluationFormat::class, 'evaluation_format_id');
    }

    private function syncNameFromContent(?string $html): void
    {
        if ($html === null || $html === '') {
            $this->attributes['name'] = 'Untitled criterion';

            return;
        }

        $plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
        $this->attributes['name'] = $plain === ''
            ? 'Untitled criterion'
            : Str::limit($plain, 500, '');
    }
}
