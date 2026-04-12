<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ResearchPaper extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'sdg_id',
        'agenda_id',
        'title',
        'abstract',
        'proponents',
        'tracking_id',
        'status',
        'submission_date',
        'publication_date',
        'keywords',
        'views',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'publication_date' => 'date',
        'proponents' => 'array',
        'views' => 'integer',
    ];

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
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sdg(): BelongsTo
    {
        return $this->belongsTo(Sdg::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
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

    public function publication(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function trackingRecords(): HasMany
    {
        return $this->hasMany(TrackingRecord::class)->latest('status_changed_at');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByAuthor($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getProgressAttribute(): int
    {
        $stages = ['submitted' => 0, 'under_review' => 25, 'approved' => 50, 'presented' => 75, 'published' => 100, 'archived' => 100];

        return $stages[$this->status] ?? 0;
    }
}
