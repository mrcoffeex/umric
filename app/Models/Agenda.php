<?php

namespace App\Models;

use Database\Factories\AgendaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'code', 'description', 'is_active'])]
class Agenda extends Model
{
    /** @use HasFactory<AgendaFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
