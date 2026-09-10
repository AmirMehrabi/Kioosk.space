<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EntitySeoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function schemaTypes(): array
    {
        return [
            'restaurant' => ['رستوران', 'Restaurant'],
            'cafe' => ['کافه', 'CafeOrCoffeeShop'],
            'store' => ['فروشگاه', 'Store'],
            'general' => ['پزشک', 'LocalBusiness'],
        ];
    }

    #[DataProvider('schemaTypes')]
    public function test_place_page_is_server_rendered_with_entity_metadata_and_schema(string $categoryName, string $schemaType): void
    {
        $category = Category::where('name', $categoryName)->first() ?? Category::create(['name' => $categoryName]);
        $business = Business::factory()->create(['name' => 'برگر باغ فردوس', 'slug' => 'burger-garden-ferdows', 'category_id' => $category->id]);
        $author = User::factory()->create(['name' => 'Amir']);
        Review::create(['business_id' => $business->id, 'user_id' => $author->id, 'rating' => 5, 'body' => 'برگر عالی و فضای دلنشین', 'visit_date' => '2026-09-10', 'status' => 'published']);

        $response = $this->get(route('businesses.show', $business->slug));

        $response->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('برگر باغ فردوس')
            ->assertSee('از ۵، بر اساس 1 نظر')
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="'.route('businesses.show', $business->slug).'">', false)
            ->assertSee('"@type":"'.$schemaType.'"', false)
            ->assertSee('"@type":"Review"', false)
            ->assertSee('"ratingValue":5', false)
            ->assertSee('"reviewBody":"برگر عالی و فضای دلنشین"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_user_category_and_city_pages_expose_connected_entities(): void
    {
        $business = Business::factory()->create(['name' => 'کافه نمونه']);
        $user = User::factory()->create(['name' => 'Amir']);
        $review = Review::create(['business_id' => $business->id, 'user_id' => $user->id, 'rating' => 4, 'body' => 'تجربه قابل پیشنهاد', 'visit_date' => '2026-09-10', 'status' => 'published']);
        DB::table('helpful_votes')->insert(['user_id' => User::factory()->create()->id, 'review_id' => $review->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->get(route('users.show', $user->slug))->assertOk()->assertSee('1')->assertSee('تجربه قابل پیشنهاد')->assertSee('"@type":"Person"', false);
        $this->get(route('categories.show', $business->category->slug))->assertOk()->assertSee('کافه نمونه');
        $this->get(route('cities.show', $business->location->slug))->assertOk()->assertSee('کافه نمونه');
    }

    public function test_sitemap_index_and_children_only_include_public_entities(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();
        Review::create(['business_id' => $business->id, 'user_id' => $user->id, 'rating' => 4, 'body' => 'یک تجربه منتشرشده', 'visit_date' => '2026-09-10', 'status' => 'published']);
        $hidden = Business::factory()->create(['status' => 'pending']);

        $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee(route('sitemap.places'));
        $this->get(route('sitemap.places'))->assertSee(route('businesses.show', $business->slug))->assertDontSee($hidden->slug);
        $this->get(route('sitemap.categories'))->assertSee(route('categories.show', $business->category->slug));
        $this->get(route('sitemap.cities'))->assertSee(route('cities.show', $business->location->slug));
        $this->get(route('sitemap.users'))->assertSee(route('users.show', $user->slug));
    }
}
