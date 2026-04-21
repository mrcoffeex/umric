<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['name', 'email', 'password', 'google_id', 'blocked_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email'])->logOnlyDirty();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function role(): string
    {
        return $this->profile?->role ?? 'student';
    }

    public function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role() === 'staff';
    }

    public function isFaculty(): bool
    {
        return $this->role() === 'faculty';
    }

    public function isStudent(): bool
    {
        return $this->role() === 'student';
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    public function isApproved(): bool
    {
        return $this->isFaculty() ? (bool) $this->profile?->approved_at : true;
    }

    public function isBlocked(): bool
    {
        return ! is_null($this->blocked_at);
    }

    public function researchPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class);
    }

    public function advisedPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class, 'adviser_id');
    }

    public function statisticianPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class, 'statistician_id');
    }

    public function authoredPapers(): BelongsToMany
    {
        return $this->belongsToMany(ResearchPaper::class, 'paper_authors')
            ->withPivot('author_order')
            ->orderByPivot('author_order');
    }

    public function trackingRecords(): HasMany
    {
        return $this->hasMany(TrackingRecord::class, 'updated_by');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }
}
