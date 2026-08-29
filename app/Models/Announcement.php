<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Announcement extends Model
{
    use HasFactory, HasUlids;

    public const MAX_THUMBNAILS = 5;

    protected $fillable = [
        'title',
        'content',
        'thumbnails',
        'type',
        'is_pinned',
        'is_active',
        'target_roles',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
        'target_roles' => 'array',
        'thumbnails' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return list<array{path: string, url: string}>
     */
    public function thumbnailPayload(): array
    {
        return collect($this->thumbnails ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>|null  $paths
     */
    public function deleteThumbnailFiles(?array $paths = null): void
    {
        foreach ($paths ?? ($this->thumbnails ?? []) as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('target_roles')
                ->orWhereJsonContains('target_roles', $role);
        });
    }
}
