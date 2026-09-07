<?php

namespace Database\Seeders;

use App\Models\BusinessClaimProof;
use Illuminate\Database\Seeder;

class BusinessClaimProofSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BusinessClaimProof::factory()->create();
    }
}
