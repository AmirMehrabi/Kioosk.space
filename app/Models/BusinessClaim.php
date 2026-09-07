<?php

namespace App\Models;

use Database\Factories\BusinessClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessClaim extends Model
{
    /** @use HasFactory<BusinessClaimFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(BusinessClaimProof::class);
    }
}
