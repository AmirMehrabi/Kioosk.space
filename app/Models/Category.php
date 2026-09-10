<?php

namespace App\Models;

use App\Support\EntitySlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(fn (Category $category) => EntitySlug::set($category));
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
