<?php

namespace Database\Factories;

use App\Models\BusinessClaim;
use App\Models\BusinessClaimProof;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessClaimProof>
 */
class BusinessClaimProofFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_claim_id' => BusinessClaim::factory(),
            'path' => 'business-claims/proof.jpg',
            'thumbnail_path' => 'business-claims/proof-thumb.jpg',
        ];
    }
}
