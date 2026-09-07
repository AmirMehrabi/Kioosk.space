<?php

namespace Database\Seeders;

use App\Models\BusinessClaim;
use Illuminate\Database\Seeder;

class BusinessClaimSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BusinessClaim::factory()->create();
    }
}
