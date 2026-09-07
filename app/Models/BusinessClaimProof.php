<?php

namespace App\Models;

use Database\Factories\BusinessClaimProofFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessClaimProof extends Model
{
    /** @use HasFactory<BusinessClaimProofFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['path', 'thumbnail_path'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(BusinessClaim::class, 'business_claim_id');
    }
}
