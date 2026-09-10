<?php

namespace App\Models;

use App\Support\EntitySlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(fn (City $city) => EntitySlug::set($city));
    }

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'is_active' => 'boolean'];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
