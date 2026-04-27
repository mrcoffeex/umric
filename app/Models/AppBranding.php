<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppBranding extends Model
{
    protected $fillable = [
        'site_name',
        'tagline',
        'logo_path',
    ];

    public function logoPublicUrl(): ?string
    {
        if ($this->logo_path === null || $this->logo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
