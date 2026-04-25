<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HandoffEsignatureStorage
{
    public const DATA_URL_PREFIX = 'data:image/png;base64,';

    public static function storePngDataUrlForTransmission(string $dataUrl, string $transmissionId): string
    {
        if (! str_starts_with($dataUrl, self::DATA_URL_PREFIX)) {
            throw new \InvalidArgumentException('Signature must be a PNG data URL.');
        }

        $encoded = substr($dataUrl, strlen(self::DATA_URL_PREFIX));
        $raw = base64_decode($encoded, true);

        if ($raw === false) {
            throw new \InvalidArgumentException('Invalid base64 in signature data URL.');
        }

        $path = 'document-handoffs/esignatures/'.$transmissionId.'/'.Str::ulid().'.png';
        Storage::disk('public')->put($path, $raw);

        return $path;
    }

    /**
     * Store the signer's PNG in a stable per-user path (replaces any previous file).
     */
    public static function storePngDataUrlForUserAccount(string $dataUrl, string $userId): string
    {
        if (! str_starts_with($dataUrl, self::DATA_URL_PREFIX)) {
            throw new \InvalidArgumentException('Signature must be a PNG data URL.');
        }

        $encoded = substr($dataUrl, strlen(self::DATA_URL_PREFIX));
        $raw = base64_decode($encoded, true);

        if ($raw === false) {
            throw new \InvalidArgumentException('Invalid base64 in signature data URL.');
        }

        $path = 'user-esignatures/'.$userId.'/account.png';
        Storage::disk('public')->put($path, $raw);

        return $path;
    }

    public static function copyPublicToTransmission(string $sourceRelativePath, string $transmissionId): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($sourceRelativePath)) {
            throw new \InvalidArgumentException('Source signature file is missing.');
        }

        $raw = $disk->get($sourceRelativePath);
        if (! is_string($raw) || $raw === '') {
            throw new \InvalidArgumentException('Source signature file is empty.');
        }

        $path = 'document-handoffs/esignatures/'.$transmissionId.'/'.Str::ulid().'.png';
        $disk->put($path, $raw);

        return $path;
    }
}
