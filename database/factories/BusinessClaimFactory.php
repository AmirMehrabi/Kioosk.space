<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessClaim>
 */
class BusinessClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'note' => fake()->paragraph(),
            'status' => 'pending',
            'open_key' => fn (array $attributes): string => $attributes['business_id'].':'.$attributes['user_id'],
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved', 'open_key' => null, 'decision_reason' => 'مدارک معتبر بود.', 'decided_at' => now()]);
    }
}
