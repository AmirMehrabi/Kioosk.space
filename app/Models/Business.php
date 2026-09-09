<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    public const PRICE_RANGES = [1 => 'اقتصادی', 2 => 'متوسط', 3 => 'گران', 4 => 'بسیار گران'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'phones' => 'array', 'websites' => 'array', 'weekly_hours' => 'array', 'price_range' => 'integer', 'latitude' => 'float', 'longitude' => 'float'];
    }

    public function heroPhoto(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'hero_media_id')->published();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->published();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Media::class)->published();
    }

    public function featuredPhotos(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'business_featured_media', 'business_id', 'media_id')->where('media.status', 'published')->withPivot('position')->orderByPivot('position');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(BusinessClaim::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->wherePivot('role', 'owner')->wherePivotNotNull('approved_at')->withPivot(['role', 'approved_at'])->withTimestamps();
    }
}
