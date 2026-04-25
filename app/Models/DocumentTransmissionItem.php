<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class DocumentTransmissionItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'document_transmission_id',
        'source_item_id',
        'label',
        'file_path',
        'file_name',
        'file_size',
        'disk',
        'sort_order',
        'received_at',
        'pdf_esignature_embed_count',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'pdf_esignature_embed_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (DocumentTransmissionItem $item): void {
            if ($item->file_path && $item->disk) {
                Storage::disk($item->disk)->delete($item->file_path);
            }
        });
    }

    public function transmission(): BelongsTo
    {
        return $this->belongsTo(DocumentTransmission::class, 'document_transmission_id');
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_item_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DocumentTransmissionItemActivity::class, 'document_transmission_item_id')
            ->orderBy('created_at');
    }

    public function hasAttachment(): bool
    {
        return $this->file_path !== null && $this->file_path !== '';
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function logActivity(string $event, ?string $userId, array $meta = []): DocumentTransmissionItemActivity
    {
        return $this->activities()->create([
            'user_id' => $userId,
            'event' => $event,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
