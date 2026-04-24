<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentTransmissionItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'document_transmission_id',
        'label',
        'file_path',
        'file_name',
        'file_size',
        'disk',
        'sort_order',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
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

    public function hasAttachment(): bool
    {
        return $this->file_path !== null && $this->file_path !== '';
    }
}
