<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDefense extends Model
{
    use HasFactory;

    public const DEFENSE_TYPES = [
        'title' => 'Title Defense',
        'outline' => 'Outline Defense',
        'final' => 'Final Defense',
    ];

    protected $fillable = [
        'research_paper_id',
        'defense_type',
        'panel_members',
        'schedule',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'panel_members' => 'array',
        'schedule' => 'datetime',
    ];

    public function researchPaper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDefenseTypeLabelAttribute(): string
    {
        return self::DEFENSE_TYPES[$this->defense_type] ?? ucfirst($this->defense_type);
    }
}
