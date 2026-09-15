<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class RatingComponentTest extends TestCase
{
    public function test_fractional_rating_fills_the_next_star_from_right_to_left(): void
    {
        $html = Blade::render('<x-rating :value="3.4" />');

        $this->assertStringContainsString('data-rating-star="4"', $html);
        $this->assertStringContainsString('data-fill="40"', $html);
        $this->assertStringContainsString('class="absolute inset-y-0 right-0 overflow-hidden"', $html);
        $this->assertStringContainsString('width: 40%;', $html);
    }

    public function test_half_rating_fills_exactly_half_of_the_next_star(): void
    {
        $html = Blade::render('<x-review-stars :rating="4.5" />');

        $this->assertStringContainsString('data-rating-star="5"', $html);
        $this->assertStringContainsString('data-fill="50"', $html);
        $this->assertStringContainsString('width: 50%;', $html);
    }

    public function test_higher_ratings_use_a_darker_color_than_lower_ratings(): void
    {
        $lowRating = Blade::render('<x-rating :value="1" />');
        $highRating = Blade::render('<x-rating :value="5" />');

        $this->assertStringContainsString('data-rating-color="#ff8a65"', $lowRating);
        $this->assertStringContainsString('data-rating-color="#a92330"', $highRating);
    }

    public function test_small_size_uses_the_larger_rounded_star_tiles(): void
    {
        $html = Blade::render('<x-rating :value="4" />');

        $this->assertStringContainsString('size-8 rounded-[9px]', $html);
        $this->assertStringContainsString('M11.3 2.9', $html);
    }

    public function test_dynamic_rating_keeps_fractional_fill_behavior(): void
    {
        $html = Blade::render('<x-rating dynamic="average" />');

        $this->assertStringContainsString('Number(average) - 0', $html);
        $this->assertStringContainsString('background-color:', $html);
        $this->assertStringContainsString('x-bind:aria-label="fa(average)', $html);
    }
}
