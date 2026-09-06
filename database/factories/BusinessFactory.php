<?php

namespace Database\Factories;

use App\Support\BusinessIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BusinessFactory extends Factory
{
    public function definition(): array
    {
        $data = ['name' => fake()->unique()->company(), 'city' => 'تهران', 'address' => fake()->unique()->streetAddress()];

        return $data + ['category_id' => 1, 'slug' => (string) Str::uuid(), 'status' => 'approved', 'normalized_name' => BusinessIdentity::normalize($data['name']), 'normalized_city' => 'تهران', 'fingerprint' => BusinessIdentity::fingerprint($data)];
    }
}
