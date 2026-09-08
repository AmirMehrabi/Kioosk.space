<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homepage_restores_the_original_layout_and_keeps_discovery_separate(): void
    {
        $tehran = Business::factory()->create();
        $rasht = Business::factory()->create(['city' => 'رشت', 'normalized_city' => 'رشت']);

        $response = $this->withSession(['discovery.city' => 'رشت'])->get(route('home'));

        $response->assertOk()->assertSee('کجا بریم؟')->assertSee('پیشنهادهای خوب، نزدیک شما')
            ->assertSee('تازه‌ترین تجربه‌ها')->assertSee('مکان‌های شهر')->assertSee(route('discovery'))
            ->assertDontSee('id="discovery-map"', false)->assertDontSee('id="discovery-data"', false);
        $this->assertSame([$rasht->id, $tehran->id], $response->viewData('businesses')->pluck('id')->all());
        $this->get(route('discovery'))->assertOk()->assertViewIs('discovery')->assertSee('id="discovery-map"', false)
            ->assertSee('action="'.route('discovery').'#places"', false);
    }

    public function test_homepage_shows_the_six_newest_public_reviews_in_order(): void
    {
        $reviews = collect();
        for ($index = 0; $index < 7; $index++) {
            $reviews->push($this->review(['created_at' => '2026-09-0'.($index + 1).' 12:00:00']));
        }

        $response = $this->get(route('home'));

        $response->assertOk()->assertSee($reviews->last()->author->name)->assertSee($reviews->last()->body)
            ->assertSee(route('reviews.show', $reviews->last()));
        $this->assertSame($reviews->reverse()->take(6)->pluck('id')->values()->all(), $response->viewData('recentReviews')->pluck('id')->all());
    }

    public function test_recent_reviews_exclude_unpublished_deleted_and_unapproved_business_content(): void
    {
        $public = $this->review();
        $this->review(['status' => 'pending', 'body' => 'نظر در انتظار بررسی']);
        $this->review(['status' => 'hidden', 'body' => 'نظر پنهان']);
        $this->review(['body' => 'نظر حذف شده'])->delete();
        $this->review(['body' => 'کسب‌وکار خصوصی'], ['status' => 'pending']);

        $response = $this->get(route('home'));

        $response->assertOk()->assertDontSee('نظر در انتظار بررسی')->assertDontSee('نظر پنهان')->assertDontSee('نظر حذف شده')->assertDontSee('کسب‌وکار خصوصی');
        $this->assertSame([$public->id], $response->viewData('recentReviews')->pluck('id')->all());
    }

    public function test_recent_reviews_follow_homepage_filters(): void
    {
        $matching = $this->review([], ['name' => 'کافه آفتاب', 'normalized_name' => 'کافه آفتاب', 'city' => 'رشت', 'normalized_city' => 'رشت', 'category_id' => 2]);
        $this->review([], ['normalized_name' => 'آفتاب', 'category_id' => 2]);
        $this->review([], ['normalized_name' => 'آفتاب', 'city' => 'رشت', 'normalized_city' => 'رشت', 'category_id' => 1]);
        $this->review([], ['normalized_name' => 'دیگر', 'city' => 'رشت', 'normalized_city' => 'رشت', 'category_id' => 2]);

        $response = $this->get(route('home', ['city' => 'رشت', 'category' => 2, 'query' => 'آفتاب']));

        $response->assertOk();
        $this->assertSame([$matching->id], $response->viewData('recentReviews')->pluck('id')->all());
    }

    public function test_review_cards_escape_text_and_include_only_published_photos(): void
    {
        $body = '<script>alert("review")</script>';
        $review = $this->review(['body' => $body]);
        $photos = [];
        foreach (['published', 'pending'] as $status) {
            $photos[$status] = Media::create([
                'id' => (string) Str::uuid(), 'client_id' => (string) Str::uuid(), 'user_id' => $review->user_id,
                'business_id' => $review->business_id, 'review_id' => $review->id,
                'status' => $status, 'path' => 'full.jpg', 'thumbnail_path' => 'thumb.jpg',
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk()->assertSee($body)->assertDontSee($body, false)
            ->assertSee(route('media.show', [$photos['published'], 'thumbnail' => 1]))
            ->assertDontSee(route('media.show', [$photos['pending'], 'thumbnail' => 1]));
        $this->assertSame([$photos['published']->id], $response->viewData('recentReviews')->first()->photos->pluck('id')->all());
    }

    private function review(array $attributes = [], array $businessAttributes = []): Review
    {
        return Review::create($attributes + [
            'business_id' => Business::factory()->create($businessAttributes)->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4, 'body' => 'فضای آرام و برخورد خوبی داشتند.', 'visit_date' => '2026-09-01', 'status' => 'published',
        ]);
    }
}
