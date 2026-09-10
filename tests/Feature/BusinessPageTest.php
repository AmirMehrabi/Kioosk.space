<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessSpecification;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use App\Support\DemoBusinesses;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BusinessPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function businesses(): array
    {
        return [['cafe-rira', 'کافه ری‌را'], ['restaurant-gilaneh', 'رستوران گیلانه'], ['bakery-sahar', 'نانوایی سحر']];
    }

    #[DataProvider('businesses')]
    public function test_business_page_renders_gallery_information_and_review_controls(string $slug, string $name): void
    {
        Business::factory()->create(['slug' => $slug, 'name' => $name]);
        $this->get('/businesses/'.$slug)->assertOk()->assertSee($name)->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('گالری تصاویر')->assertSee('آدرس و اطلاعات تماس')->assertSee('نوشتن تجربه من')->assertSee('تجربه‌های مردم')
            ->assertDontSee('فقط در همین مرورگر');
    }

    public static function photoCounts(): array
    {
        return [[1], [2], [3], [4], [5]];
    }

    #[DataProvider('photoCounts')]
    public function test_photo_gallery_precedes_business_details_and_links_to_all_photos(int $photoCount): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();
        for ($index = 0; $index < $photoCount; $index++) {
            Media::create([
                'id' => (string) Str::uuid(), 'user_id' => $user->id, 'client_id' => (string) Str::uuid(),
                'business_id' => $business->id, 'path' => 'full.jpg', 'thumbnail_path' => 'thumb.jpg',
                'status' => 'published', 'source' => 'management',
            ]);
        }

        $response = $this->get('/businesses/'.$business->slug);

        $response->assertOk()->assertSeeInOrder(['aria-label="تصاویر اصلی کسب‌وکار"', 'href="#gallery"', '<h1', 'id="gallery"'], false)
            ->assertSee('مشاهده همه تصاویر ('.$photoCount.')')
            ->assertSee('data-open-business-gallery', false)->assertSee('data-media-skeleton', false);
        foreach ($business->photos as $photo) {
            $response->assertSee(route('media.show', [$photo, 'thumbnail' => 1]));
        }
    }

    public function test_unknown_business_returns_not_found(): void
    {
        $this->get('/businesses/nonexistent-business')->assertNotFound();
    }

    public function test_business_page_only_renders_selected_true_specifications(): void
    {
        $business = Business::factory()->create();
        $petFriendly = BusinessSpecification::where('key', 'pet_friendly')->firstOrFail();
        $smoking = BusinessSpecification::where('key', 'smoking_allowed')->firstOrFail();
        $business->specifications()->attach($petFriendly);

        $this->get(route('businesses.show', $business->slug))
            ->assertSee('امکانات و ویژگی‌ها')
            ->assertSee($petFriendly->label)
            ->assertDontSee($smoking->label);
    }

    public function test_business_page_hides_specification_section_when_none_are_true(): void
    {
        $business = Business::factory()->create();

        $this->get(route('businesses.show', $business->slug))
            ->assertDontSee('امکانات و ویژگی‌ها');
    }

    public function test_gallery_filters_photos_by_category_and_keeps_uncategorized_photos_in_other(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();
        $interior = $this->photo($business, $user, 'interior', 'inside.jpg');
        $exterior = $this->photo($business, $user, 'exterior', 'outside.jpg');
        $uncategorized = $this->photo($business, $user, null, 'other.jpg');

        $this->getJson(route('businesses.show', [$business->slug, 'photo_category' => 'interior']))
            ->assertOk()->assertJsonCount(1, 'photos')->assertJsonPath('photos.0.id', $interior->id)
            ->assertJsonPath('counts.all', 3)->assertJsonPath('counts.interior', 1);

        $this->getJson(route('businesses.show', [$business->slug, 'photo_category' => 'other']))
            ->assertOk()->assertJsonCount(1, 'photos')->assertJsonPath('photos.0.id', $uncategorized->id);
        $this->getJson(route('businesses.show', [$business->slug, 'photo_category' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('photo_category');
        $this->assertNotSame($exterior->id, $interior->id);
    }

    public function test_homepage_review_cards_link_to_their_businesses(): void
    {
        foreach (self::businesses() as [$slug, $name]) {
            $business = Business::factory()->create(['slug' => $slug, 'name' => $name]);
            Review::create(['business_id' => $business->id, 'user_id' => User::factory()->create()->id, 'rating' => 4, 'body' => 'یک تجربه خوب و به‌یادماندنی.', 'visit_date' => '2026-09-01', 'status' => 'published']);
        }
        $response = $this->get('/')->assertOk();
        foreach (self::businesses() as [$slug, $name]) {
            $response->assertSee(route('businesses.show', $slug), false);
        }
    }

    private function photo(Business $business, User $user, ?string $category, string $path): Media
    {
        return Media::create([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'client_id' => (string) Str::uuid(),
            'business_id' => $business->id, 'path' => $path, 'thumbnail_path' => 'thumb-'.$path,
            'status' => 'published', 'source' => 'management', 'category' => $category,
        ]);
    }

    public function test_city_search_is_backed_by_the_managed_city_list(): void
    {
        $this->get('/')->assertOk()->assertSee('role="combobox"', false)->assertSee('شاهین‌شهر')->assertSee('بندر انزلی');
        $this->get('/?city=ناشناخته')->assertRedirect()->assertSessionHasErrors('city');

        $this->artisan('kioosk:city', ['name' => 'یزد'])->assertSuccessful();
        $this->assertDatabaseHas('cities', ['name' => 'یزد']);
        $this->get('/')->assertOk()->assertSee('یزد');
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
