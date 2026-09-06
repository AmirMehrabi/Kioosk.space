<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || ! config('contributions.demo')) {
            throw new \RuntimeException('Demo seeding requires a development environment and KIOOSK_DEMO=true.');
        }
        Business::factory()->count(3)->create();
    }
}
