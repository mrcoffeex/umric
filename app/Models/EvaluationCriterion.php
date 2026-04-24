<?php

namespace App\Models;

use Database\Factories\EvaluationCriterionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @use HasFactory<EvaluationCriterionFactory>
 */
class EvaluationCriterion extends Model
{
    use HasFactory, HasUlids;

    public const MAX_TOTAL = 100;

    protected $fillable = [
        'name',
        'max_points',
        'sort_order',
    ];

    public static function currentTotalMax(): int
    {
        return (int) self::query()->sum('max_points');
    }
}
