<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Business;
use App\Models\Media;
use App\Models\User;
use App\Services\BusinessHours;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $business->refresh();
        $this->assertSame('رزرو', $business->phones[0]['label']);
        $this->assertSame('09:00', $business->weekly_hours['saturday']['shifts'][0]['opens']);
        $this->assertSame($photo->id, $business->featuredPhotos()->firstOrFail()->id);
        $this->assertDatabaseHas('moderation_history', ['content_type' => 'business', 'content_id' => (string) $business->id, 'action' => 'profile_update']);
        $this->get(route('businesses.show', $business->slug))->assertOk()->assertSee('معرفی کامل کسب‌وکار')->assertSee('رزرو');
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
        ])->assertCreated()->json('id');
        $photo = Media::findOrFail($photoId);
        Storage::disk('local')->assertExists($photo->path);
        $this->deleteJson(route('business.businesses.photos.destroy', [$business, $photo]))->assertOk();
        Storage::disk('local')->assertMissing($photo->path);
        $this->assertSame('removed', $photo->fresh()->status);
    }

    public function test_recent_admin_can_manage_any_approved_business(): void
    {
        $admin = $this->verifiedUser(['platform_role' => PlatformRole::Admin]);
        $business = Business::factory()->create();

        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->get(route('admin.businesses.edit', $business))->assertOk()->assertSee($business->name);
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
}
