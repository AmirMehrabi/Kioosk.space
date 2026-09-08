<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\City;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_discovery_filters_city_category_and_name_without_exposing_private_businesses(): void
    {
        $match = Business::factory()->create(['name' => 'کافه کیوسک', 'normalized_name' => 'کافه کیوسک', 'category_id' => 2, 'latitude' => 35.7, 'longitude' => 51.4, 'price_range' => 2]);
        Business::factory()->create(['name' => 'نام پنهان', 'normalized_name' => 'کیوسک', 'category_id' => 2, 'status' => 'pending']);
        Business::factory()->create(['normalized_name' => 'کیوسک', 'category_id' => 1]);
        Business::factory()->create(['normalized_name' => 'کیوسک', 'category_id' => 2, 'city' => 'رشت', 'normalized_city' => 'رشت']);

        $response = $this->get(route('discovery', ['city' => 'تهران', 'category' => 2, 'query' => 'كيوسك']));

        $response->assertOk()->assertDontSee('نام پنهان')->assertSee('بازه قیمت: متوسط');
        $this->assertSame([$match->id], $response->viewData('businesses')->pluck('id')->all());
        $this->assertSame([[
            'id' => $match->id, 'name' => 'کافه کیوسک', 'address' => $match->address,
            'latitude' => 35.7, 'longitude' => 51.4, 'url' => route('businesses.show', $match->slug),
            'rating' => null, 'reviews' => 0, 'price' => 2,
        ]], $response->viewData('mapBusinesses')->all());
    }

    #[TestWith(['کافه'])]
    #[TestWith(['انقلاب'])]
    public function test_discovery_finds_categories_and_addresses_without_a_matching_business_name(string $term): void
    {
        $business = Business::factory()->create(['name' => 'ری‌را', 'normalized_name' => 'ری را', 'category_id' => 2, 'address' => 'خیابان انقلاب']);
        Business::factory()->create(['category_id' => 1]);

        $response = $this->get(route('discovery', ['query' => $term, 'city' => 'تهران']));

        $response->assertOk();
        $this->assertSame([$business->id], $response->viewData('businesses')->pluck('id')->all());
    }

    public function test_selected_city_is_remembered_and_empty_results_keep_its_map_center(): void
    {
        $this->get(route('discovery', ['city' => 'رشت', 'query' => 'مکان ناموجود']))
            ->assertOk()->assertSee('مکانی با این جست‌وجو پیدا نشد')->assertSessionHas('discovery.city', 'رشت');

        $response = $this->get(route('discovery'));

        $this->assertSame('رشت', $response->viewData('city')->name);
        $this->assertSame(37.2808, $response->viewData('city')->latitude);
        $this->assertSame(49.5832, $response->viewData('city')->longitude);
    }

    public function test_unlocated_businesses_stay_visible_and_pagination_keeps_map_and_list_in_sync(): void
    {
        Business::factory()->count(13)->create();

        $response = $this->get(route('discovery', ['city' => 'تهران', 'page' => 2]));

        $response->assertOk()->assertSee('موقعیت دقیق ثبت نشده')->assertDontSee('data-focus-business=', false);
        $this->assertCount(1, $response->viewData('mapBusinesses'));
        $this->assertSame($response->viewData('businesses')->pluck('id')->all(), $response->viewData('mapBusinesses')->pluck('id')->all());
        $this->assertNull($response->viewData('mapBusinesses')->first()['latitude']);
        $this->assertStringContainsString('city=', $response->viewData('businesses')->previousPageUrl());
    }

    public function test_map_payload_and_result_names_escape_html(): void
    {
        $name = '</script><script>alert("x")</script>';
        Business::factory()->create(['name' => $name, 'latitude' => 35.7, 'longitude' => 51.4]);

        $this->get(route('discovery'))->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    #[TestWith(['category' => 99999])]
    #[TestWith(['category' => 'invalid'])]
    #[TestWith(['city' => 'نامعتبر'])]
    #[TestWith(['query' => ['invalid']])]
    public function test_discovery_rejects_invalid_filters(mixed $category = null, mixed $city = null, mixed $query = null): void
    {
        $parameters = array_filter(compact('category', 'city', 'query'), fn ($value) => $value !== null);

        $this->getJson(route('discovery', $parameters))->assertUnprocessable()->assertJsonValidationErrors(array_keys($parameters));
    }

    public function test_city_command_can_set_and_update_the_map_center(): void
    {
        $this->artisan('kioosk:city', ['name' => 'یزد', '--latitude' => '31.89', '--longitude' => '54.36'])->assertSuccessful();
        $this->assertDatabaseHas('cities', ['name' => 'یزد', 'latitude' => 31.89, 'longitude' => 54.36]);

        $this->artisan('kioosk:city', ['name' => 'یزد', '--latitude' => '31.9', '--longitude' => '54.4'])->assertSuccessful();
        $response = $this->get(route('discovery', ['city' => 'یزد']));
        $this->assertSame(31.9, $response->viewData('city')->latitude);
        $this->assertSame(54.4, $response->viewData('city')->longitude);
        $this->assertSame(1, City::where('name', 'یزد')->count());
    }

    public function test_city_command_rejects_partial_coordinates_without_creating_the_city(): void
    {
        $this->artisan('kioosk:city', ['name' => 'یزد', '--latitude' => '31.89'])->assertFailed();

        $this->assertDatabaseMissing('cities', ['name' => 'یزد']);
    }
}
