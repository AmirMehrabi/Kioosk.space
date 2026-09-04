<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Business extends Model
{
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->wherePivot('role', 'owner')->wherePivotNotNull('approved_at')->withPivot(['role', 'approved_at'])->withTimestamps();
    }
}
