<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperAuthor extends Model
{
    use HasUlids;

    protected $table = 'paper_authors';

    protected $fillable = [
        'research_paper_id',
        'user_id',
        'author_order',
    ];

    public $timestamps = true;

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
