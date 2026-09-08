<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homepage_has_navbar_search_a_social_feed_and_category_links_to_discovery(): void
    {
        $tehran = $this->review();
        $rasht = $this->review([], ['city' => 'رشت', 'normalized_city' => 'رشت']);

        $response = $this->withSession(['discovery.city' => 'رشت'])->get(route('home'));

        $response->assertOk()->assertSee('هر گوشهٔ شهر،')->assertSee('یک تجربهٔ خوب.')
            ->assertSee('تازه‌ترین تجربه‌ها')->assertSee('امروز دنبال چی می‌گردی؟')->assertSee(route('discovery'))
            ->assertSee('id="navbar-query"', false)->assertDontSee('id="places"', false)
            ->assertDontSee('id="discovery-map"', false)->assertDontSee('id="discovery-data"', false);
        $this->assertSame([$rasht->id, $tehran->id], $response->viewData('recentReviews')->pluck('id')->all());
        $this->get(route('discovery'))->assertOk()->assertViewIs('discovery')->assertSee('id="discovery-map"', false)
            ->assertSee('action="'.route('discovery').'#places"', false);
    }

    public function test_homepage_shows_twelve_newest_public_reviews_and_offers_load_more(): void
    {
        $reviews = collect();
        for ($index = 0; $index < 13; $index++) {
            $reviews->push($this->review(['created_at' => sprintf('2026-09-%02d 12:00:00', $index + 1)]));
        }

        $response = $this->get(route('home'));

        $response->assertOk()->assertSee($reviews->last()->author->name)->assertSee($reviews->last()->body)
            ->assertSee(route('reviews.show', $reviews->last()))->assertSee('data-load-reviews', false);
        $this->assertSame($reviews->reverse()->take(12)->pluck('id')->values()->all(), $response->viewData('recentReviews')->pluck('id')->all());
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

    public function test_load_more_returns_the_next_reviews_without_page_chrome_and_stops_at_the_end(): void
    {
        $reviews = collect();
        for ($index = 0; $index < 13; $index++) {
            $reviews->push($this->review(['body' => 'تجربه شماره '.$index]));
        }

        $first = $this->getJson(route('home'))->assertOk()->assertJsonStructure(['html', 'next']);
        $this->assertSame(12, substr_count($first->json('html'), 'data-review-id='));
        $this->assertStringNotContainsString('<header', $first->json('html'));
        $this->assertNotNull($first->json('next'));

        $second = $this->getJson($first->json('next'))->assertOk()->assertJsonPath('next', null);
        $this->assertSame(1, substr_count($second->json('html'), 'data-review-id='));
        $this->assertStringContainsString('data-review-id="'.$reviews->first()->id.'"', $second->json('html'));
        $this->assertStringNotContainsString('data-review-id="'.$reviews->first()->id.'"', $first->json('html'));
        $this->getJson(route('home', ['page' => 3]))->assertExactJson(['html' => '', 'next' => null]);
    }

    #[TestWith([0])]
    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([3])]
    #[TestWith([4])]
    #[TestWith([5])]
    public function test_review_gallery_displays_zero_to_four_photos(int $count): void
    {
        $review = $this->review();
        for ($index = 0; $index < $count; $index++) {
            Media::create([
                'id' => (string) Str::uuid(), 'client_id' => (string) Str::uuid(), 'user_id' => $review->user_id,
                'business_id' => $review->business_id, 'review_id' => $review->id,
                'status' => 'published', 'path' => 'full.jpg', 'thumbnail_path' => 'thumb.jpg',
            ]);
        }

        $response = $this->get(route('home'))->assertOk();

        $this->assertCount(min($count, 4), $response->viewData('recentReviews')->first()->photos);
        if ($count === 0) {
            $response->assertDontSee('data-photo-count=', false);
        } else {
            $response->assertSee('data-photo-count="'.min($count, 4).'"', false);
        }
    }

    public function test_feed_rejects_invalid_page_numbers(): void
    {
        $this->getJson(route('home', ['page' => -1]))->assertUnprocessable()->assertJsonValidationErrors('page');
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
