<?php

namespace App\Models;

use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['faculty_id', 'name', 'class_code', 'school_year', 'semester', 'term', 'section', 'description', 'is_active', 'join_code'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory, HasUlids;

    protected $table = 'school_classes';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'semester' => 'integer',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_class_members', 'school_class_id', 'student_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'school_class_subjects')
            ->withTimestamps();
    }

    public function researchPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class);
    }

    public function generateAndSetJoinCode(): void
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('join_code', $code)->exists());

        $this->join_code = $code;
        $this->save();
    }
}
