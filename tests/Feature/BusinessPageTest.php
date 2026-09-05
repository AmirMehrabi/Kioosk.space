<?php

namespace Tests\Feature;

use App\Support\DemoBusinesses;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BusinessPageTest extends TestCase
{
    public static function businesses(): array
    {
        return [['cafe-rira', 'کافه ری‌را'], ['restaurant-gilaneh', 'رستوران گیلانه'], ['bakery-sahar', 'نانوایی سحر']];
    }

    #[DataProvider('businesses')]
    public function test_business_page_renders_gallery_information_and_review_controls(string $slug, string $name): void
    {
        $this->get('/businesses/'.$slug)->assertOk()->assertSee($name)->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('گالری تصاویر')->assertSee('آدرس و اطلاعات تماس')->assertSee('امتیاز شما')->assertSee('گفت‌وگو')
            ->assertSee('فقط در همین مرورگر');
    }

    public function test_unknown_business_returns_not_found(): void
    {
        $this->get('/businesses/nonexistent-business')->assertNotFound();
    }

    public function test_homepage_links_to_all_three_businesses(): void
    {
        $response = $this->get('/')->assertOk();
        foreach (self::businesses() as [$slug, $name]) {
            $response->assertSee(route('businesses.show', $slug), false);
        }
    }

    public function test_demo_rating_and_assets_match_the_business_data(): void
    {
        foreach (self::businesses() as [$slug, $name]) {
            $business = DemoBusinesses::find($slug);
            $this->assertSame(10, count($business['reviews']));
            $this->assertSame(4.8, array_sum(array_column($business['reviews'], 'rating')) / count($business['reviews']));
            foreach ($business['photos'] as $photo) {
                $this->assertFileExists(public_path('images/businesses/'.$photo.'.jpg'));
            }
        }
    }
}
