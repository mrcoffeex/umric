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

    /**
     * Count of handoffs that still need the user's action on the given side
     * (receiver must confirm: incoming; sender waits on recipient: outgoing).
     */
    public static function pendingCountForUser(string $userId, string $direction): int
    {
        $q = self::query()
            ->where('status', self::STATUS_PENDING);

        if ($direction === 'incoming') {
            $q->where('receiver_id', $userId);
        } else {
            $q->where('sender_id', $userId);
        }

        return $q->count();
    }

    /**
     * @param  list<string>  $labels
     */
    public static function labelSetSignature(array $labels): string
    {
        return collect($labels)
            ->map(fn (string $l) => mb_strtolower(trim($l)))
            ->filter()
            ->sort()
            ->values()
            ->implode("\0");
    }

    /**
     * A pending handoff to the same recipient with the same set of document titles (ignoring order and case).
     *
     * @param  list<string>  $itemLabels
     */
    public static function findPendingDuplicate(
        string $senderId,
        string $receiverId,
        array $itemLabels,
    ): ?self {
        $sig = self::labelSetSignature($itemLabels);
        if ($sig === '') {
            return null;
        }

        $candidates = self::query()
            ->where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('status', self::STATUS_PENDING)
            ->with(['items' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        foreach ($candidates as $t) {
            if (self::labelSetSignature($t->items->pluck('label')->all()) === $sig) {
                return $t;
            }
        }

        return null;
    }

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'forwarded_from_id',
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

    public function forwardedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'forwarded_from_id');
    }

    public function childForwards(): HasMany
    {
        return $this->hasMany(self::class, 'forwarded_from_id');
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
