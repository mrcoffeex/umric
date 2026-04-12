<?php

namespace App\Models;

use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'role',
        'department_id',
        'program_id',
        'specialization',
        'institution',
        'degree',
        'graduation_year',
        'bio',
        'profile_photo',
        'avatar_disk',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function avatarUrl(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        $disk = $this->avatar_disk ?? 'local';

        if ($disk === 's3') {
            /** @var AwsS3V3Adapter $s3 */
            $s3 = Storage::disk('s3');

            return $s3->temporaryUrl($this->profile_photo, now()->addMinutes(60));
        }

        /** @var FilesystemAdapter $local */
        $local = Storage::disk('public');

        return $local->url($this->profile_photo);
    }
}
