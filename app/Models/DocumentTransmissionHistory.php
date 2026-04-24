<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTransmissionHistory extends Model
{
    use HasUlids;

    public const EVENT_HANDOFF_CREATED = 'handoff_created';

    public const EVENT_RECEIPT_CONFIRMED = 'receipt_confirmed';

    protected $fillable = [
        'document_transmission_id',
        'user_id',
        'event',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(DocumentTransmission::class, 'document_transmission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
