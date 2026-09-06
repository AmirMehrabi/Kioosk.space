<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['path', 'thumbnail_path'];

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')->whereHas('business', fn ($query) => $query->where('status', 'approved'))
            ->where(fn ($query) => $query->whereNull('review_id')->orWhereHas('review', fn ($query) => $query->published()));
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
