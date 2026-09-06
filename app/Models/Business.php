<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->published();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Media::class)->published();
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->wherePivot('role', 'owner')->wherePivotNotNull('approved_at')->withPivot(['role', 'approved_at'])->withTimestamps();
    }
}
