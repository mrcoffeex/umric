<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingRecord extends Model
{
    protected $fillable = [
        'research_paper_id',
        'step',
        'action',
        'status',
        'old_status',
        'notes',
        'metadata',
        'updated_by',
        'status_changed_at',
    ];

    protected $casts = [
        'status_changed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper to create a log entry
    public static function log(
        int $paperId,
        string $step,
        string $action,
        string $newStatus,
        ?string $oldStatus = null,
        ?int $performedBy = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'research_paper_id' => $paperId,
            'step' => $step,
            'action' => $action,
            'status' => $newStatus,
            'old_status' => $oldStatus,
            'notes' => $notes,
            'metadata' => $metadata,
            'updated_by' => $performedBy,
            'status_changed_at' => now(),
        ]);
    }
}
