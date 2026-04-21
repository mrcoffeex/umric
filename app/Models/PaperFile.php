<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperFile extends Model
{
    use HasUlids;

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

        return route('papers.files.download', $this->id);
    }
}
