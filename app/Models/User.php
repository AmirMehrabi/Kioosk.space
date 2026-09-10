<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PlatformRole;
use App\Support\EntitySlug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(fn (User $user) => EntitySlug::set($user));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mobile_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'platform_role' => PlatformRole::class,
        ];
    }

    public function hasStaffAccess(): bool
    {
        return ! $this->suspended_at && in_array($this->platform_role, [PlatformRole::Admin, PlatformRole::Superadmin], true);
    }

    public function scopeStaff(Builder $query): void
    {
        $query->whereIn('platform_role', [PlatformRole::Admin, PlatformRole::Superadmin]);
    }

    public function ownedBusinesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)->wherePivot('role', 'owner')->wherePivotNotNull('approved_at')->withPivot(['role', 'approved_at'])->withTimestamps();
    }

    public function businessClaims(): HasMany
    {
        return $this->hasMany(BusinessClaim::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
