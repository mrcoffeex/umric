<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publication extends Model
{
    protected $fillable = [
        'research_paper_id',
        'journal_name',
        'publisher',
        'publication_date',
        'volume',
        'issue',
        'pages',
        'doi',
        'isbn',
    ];

    protected $casts = [
        'publication_date' => 'date',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }
}
