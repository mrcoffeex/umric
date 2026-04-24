<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTransmissionItemActivity extends Model
{
    use HasUlids;

    public const EVENT_ADDED = 'added';

    public const EVENT_RECEIPT_CHANGED = 'receipt_changed';

    public const EVENT_FORWARDED_IN = 'forwarded_in';

    public const EVENT_FORWARDED_OUT = 'forwarded_out';

    protected $fillable = [
        'document_transmission_item_id',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(DocumentTransmissionItem::class, 'document_transmission_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
