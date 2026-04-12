<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Citation extends Model
{
    protected $fillable = [
        'research_paper_id',
        'citation_text',
        'source_url',
        'citation_format',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }
}
