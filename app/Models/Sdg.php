<?php

namespace App\Models;

use Database\Factories\SdgFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['number', 'name', 'code', 'description', 'color', 'is_active'])]
class Sdg extends Model
{
    /** @use HasFactory<SdgFactory> */
    use HasFactory;

    protected $table = 'sdgs';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'number' => 'integer',
        ];
    }
}
