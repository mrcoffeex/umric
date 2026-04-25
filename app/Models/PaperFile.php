<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaperFile extends Model
{
    use HasUlids;

    public const CATEGORY_TITLE = 'paper';

    public const CATEGORY_OUTLINE_DEFENSE = 'outline_defense';

    public const CATEGORY_FINAL_DEFENSE = 'final_defense';

    protected $fillable = [
        'research_paper_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'file_category',
        'disk',
        'url',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }

    public function getDownloadUrlAttribute(): string
    {
        if ($this->disk === 's3') {
            return $this->url ?? '';
        }

        return Storage::disk($this->disk)->url($this->file_path);
    }
}
