<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Business;
use App\Models\BusinessSpecification;
use App\Models\Media;
use App\Models\User;
use App\Services\BusinessHours;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BusinessManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function verifiedUser(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['mobile_verified_at' => now()]);
    }

    public function test_owner_can_update_structured_profile_and_feature_published_photos(): void
    {
        $owner = $this->verifiedUser();
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);
        $photo = Media::create([
            'id' => (string) Str::uuid(), 'user_id' => $owner->id, 'client_id' => (string) Str::uuid(),
            'business_id' => $business->id, 'path' => 'full.jpg', 'thumbnail_path' => 'thumb.jpg', 'status' => 'published', 'source' => 'management',
        ]);

        $response = $this->actingAs($owner)->put(route('business.businesses.update', $business), [
            'name' => 'کافه تازه', 'category_id' => 1, 'city' => 'تهران', 'address' => 'خیابان انقلاب، پلاک ۱۰', 'description' => 'معرفی کامل کسب‌وکار',
            'phones' => [['label' => 'رزرو', 'value' => '021-12345678']],
            'websites' => [['label' => 'سایت اصلی', 'url' => 'https://example.com']],
            'weekly_hours' => $this->schedule(), 'featured_media_ids' => [$photo->id],
            'price_range' => 2, 'latitude' => 35.7, 'longitude' => 51.4,
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $business->refresh();
        $this->assertSame('رزرو', $business->phones[0]['label']);
        $this->assertSame('09:00', $business->weekly_hours['saturday']['shifts'][0]['opens']);
        $this->assertSame($photo->id, $business->featuredPhotos()->firstOrFail()->id);
        $this->assertSame(2, $business->price_range);
        $this->assertSame(35.7, $business->latitude);
        $this->assertSame(51.4, $business->longitude);
        $this->assertDatabaseHas('moderation_history', ['content_type' => 'business', 'content_id' => (string) $business->id, 'action' => 'profile_update']);
        $this->get(route('businesses.show', $business->slug))->assertOk()->assertSee('معرفی کامل کسب‌وکار')->assertSee('رزرو')->assertSee('بازه قیمت: متوسط');
        $this->get(route('business.businesses.edit', $business))->assertOk()->assertSee('data-location-picker', false)->assertSee('name="price_range"', false);
    }

    public function test_non_owner_cannot_access_management_and_cross_business_media_cannot_be_featured(): void
    {
        $owner = $this->verifiedUser();
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);
        $otherBusiness = Business::factory()->create();
        $otherPhoto = Media::create([
            'id' => (string) Str::uuid(), 'user_id' => $owner->id, 'client_id' => (string) Str::uuid(),
            'business_id' => $otherBusiness->id, 'path' => 'other.jpg', 'thumbnail_path' => 'other-thumb.jpg', 'status' => 'published', 'source' => 'management',
        ]);

        $this->actingAs($this->verifiedUser())->get(route('business.businesses.edit', $business))->assertNotFound();
        $this->actingAs($owner)->put(route('business.businesses.update', $business), [
            'name' => $business->name, 'category_id' => $business->category_id, 'city' => $business->city, 'address' => $business->address,
            'weekly_hours' => $this->schedule(), 'featured_media_ids' => [$otherPhoto->id],
        ])->assertSessionHasErrors('featured_media_ids');
    }

    public function test_owner_can_upload_and_delete_only_management_photos(): void
    {
        Storage::fake('local');
        $owner = $this->verifiedUser();
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);

        $photoId = $this->actingAs($owner)->postJson(route('business.businesses.photos.store', $business), [
            'photo' => UploadedFile::fake()->image('shop.png', 500, 400),
            'category' => 'exterior',
        ])->assertCreated()->json('id');
        $photo = Media::findOrFail($photoId);
        $this->assertSame('exterior', $photo->category->value);
        Storage::disk('local')->assertExists($photo->path);
        $this->deleteJson(route('business.businesses.photos.destroy', [$business, $photo]))->assertOk();
        Storage::disk('local')->assertMissing($photo->path);
        $this->assertSame('removed', $photo->fresh()->status);
    }

    public function test_owner_can_select_true_specifications_and_clear_them(): void
    {
        $owner = $this->verifiedUser();
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);
        $petFriendly = BusinessSpecification::where('key', 'pet_friendly')->firstOrFail();
        $wifi = BusinessSpecification::where('key', 'free_wifi')->firstOrFail();
        $profile = $business->only(['name', 'category_id', 'city', 'address']);

        $this->actingAs($owner)->put(route('business.businesses.update', $business), $profile + [
            'specifications_present' => 1,
            'specification_ids' => [$petFriendly->id, $wifi->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('business_business_specification', ['business_id' => $business->id, 'business_specification_id' => $petFriendly->id]);
        $this->assertDatabaseHas('business_business_specification', ['business_id' => $business->id, 'business_specification_id' => $wifi->id]);

        $this->actingAs($owner)->put(route('business.businesses.update', $business), $profile + ['specifications_present' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('business_business_specification', ['business_id' => $business->id]);
    }

    public function test_owner_can_categorize_only_photos_from_their_business(): void
    {
        $owner = $this->verifiedUser();
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);
        $photo = $this->photo($owner, $business);
        $otherPhoto = $this->photo($owner, Business::factory()->create());

        $this->actingAs($owner)->patchJson(route('business.businesses.photos.update', [$business, $photo]), ['category' => 'interior'])
            ->assertOk()->assertJson(['updated' => true, 'category' => 'interior']);

        $this->assertDatabaseHas('media', ['id' => $photo->id, 'category' => 'interior']);

        $this->patchJson(route('business.businesses.photos.update', [$business, $otherPhoto]), ['category' => 'exterior'])
            ->assertNotFound();
        $this->patchJson(route('business.businesses.photos.update', [$business, $photo]), ['category' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('category');
    }

    public function test_recent_admin_can_manage_any_approved_business(): void
    {
        $admin = $this->verifiedUser(['platform_role' => PlatformRole::Admin]);
        $business = Business::factory()->create();

        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->get(route('admin.businesses.edit', $business))->assertOk()->assertSee($business->name);
    }

    public function test_admin_can_set_price_and_location_and_owner_can_clear_them(): void
    {
        $admin = $this->verifiedUser(['platform_role' => PlatformRole::Admin]);
        $owner = $this->verifiedUser();
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);
        $profile = $business->only(['name', 'category_id', 'city', 'address']);

        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->put(route('admin.businesses.update', $business), $profile + ['price_range' => 4, 'latitude' => 35.75, 'longitude' => 51.45])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'price_range' => 4, 'latitude' => 35.75, 'longitude' => 51.45]);
        $this->get(route('admin.businesses.edit', $business))->assertSee('data-location-picker', false);

        $this->actingAs($owner)->put(route('business.businesses.update', $business), $profile + ['price_range' => '', 'latitude' => '', 'longitude' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'price_range' => null, 'latitude' => null, 'longitude' => null]);
    }

    #[TestWith([['price_range' => 0], 'price_range'])]
    #[TestWith([['price_range' => 5], 'price_range'])]
    #[TestWith([['price_range' => 1.5], 'price_range'])]
    #[TestWith([['price_range' => 'cheap'], 'price_range'])]
    #[TestWith([['latitude' => 35.7], 'longitude'])]
    #[TestWith([['longitude' => 51.4], 'latitude'])]
    #[TestWith([['latitude' => null], 'longitude'])]
    #[TestWith([['longitude' => null], 'latitude'])]
    #[TestWith([['latitude' => 42, 'longitude' => 51.4], 'latitude'])]
    #[TestWith([['latitude' => 35.7, 'longitude' => 65], 'longitude'])]
    public function test_invalid_price_or_location_does_not_modify_the_business(array $invalid, string $field): void
    {
        $owner = $this->verifiedUser();
        $business = Business::factory()->create(['price_range' => 2, 'latitude' => 35.7, 'longitude' => 51.4]);
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);

        $this->actingAs($owner)->putJson(route('business.businesses.update', $business), $business->only(['name', 'category_id', 'city', 'address']) + $invalid)
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'price_range' => 2, 'latitude' => 35.7, 'longitude' => 51.4]);
        $this->assertDatabaseMissing('moderation_history', ['content_id' => (string) $business->id, 'action' => 'profile_update']);
    }

    public function test_non_owner_cannot_change_price_or_location(): void
    {
        $business = Business::factory()->create(['price_range' => 2, 'latitude' => 35.7, 'longitude' => 51.4]);

        $this->actingAs($this->verifiedUser())->putJson(route('business.businesses.update', $business), $business->only(['name', 'category_id', 'city', 'address']) + ['price_range' => 4, 'latitude' => 36, 'longitude' => 52])
            ->assertForbidden();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'price_range' => 2, 'latitude' => 35.7, 'longitude' => 51.4]);
    }

    private function schedule(): array
    {
        $schedule = [];
        foreach (BusinessHours::DAYS as $day) {
            $schedule[$day] = ['closed' => true, 'shifts' => []];
        }
        $schedule['saturday'] = ['closed' => false, 'shifts' => [['opens' => '09:00', 'closes' => '13:00', 'next_day' => false], ['opens' => '16:00', 'closes' => '22:00', 'next_day' => false]]];

        return $schedule;
    }

    private function photo(User $user, Business $business): Media
    {
        return Media::create([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'client_id' => (string) Str::uuid(),
            'business_id' => $business->id, 'path' => 'full.jpg', 'thumbnail_path' => 'thumb.jpg',
            'status' => 'published', 'source' => 'management',
        ]);
    }
}
