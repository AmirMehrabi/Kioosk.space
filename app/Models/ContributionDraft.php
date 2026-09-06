<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionDraft extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'result' => 'array', 'expires_at' => 'datetime'];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
