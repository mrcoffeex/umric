<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTransmission extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'purpose',
        'share_token',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentTransmissionItem::class)
            ->orderBy('sort_order');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DocumentTransmissionHistory::class)
            ->orderBy('created_at');
    }

    public function refreshCompletionState(): void
    {
        $this->load('items');
        $allReceived = $this->items->every(fn (DocumentTransmissionItem $item) => $item->received_at !== null);

        if ($allReceived && $this->items->isNotEmpty()) {
            $this->forceFill([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->save();

            return;
        }

        $this->forceFill([
            'status' => self::STATUS_PENDING,
            'completed_at' => null,
        ])->save();
    }
}
