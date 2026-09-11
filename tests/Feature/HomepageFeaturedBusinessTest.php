<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Business;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use App\Services\UpdateBusinessProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HomepageFeaturedBusinessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_empty_homepage_has_no_featured_business(): void
    {
        $this->get(route('home'))->assertOk()->assertViewHas('featuredBusiness', null)
            ->assertSee(asset('images/businesses/cafe-counter.jpg'))
            ->assertSee('یک جای تازه، یک تجربهٔ تازه.');
    }

    public function test_businesses_are_not_featured_by_default_even_with_gallery_featured_photos(): void
    {
        $business = Business::factory()->create()->refresh();
        $photo = $this->photo($business);
        $business->featuredPhotos()->attach($photo, ['position' => 1]);
        $business->update(['hero_media_id' => $photo->id]);

        $this->get(route('home'))->assertOk()->assertViewHas('featuredBusiness', null);

        $this->assertFalse($business->is_featured);
    }

    public function test_homepage_chooses_lowest_id_eligible_business_and_loads_its_selected_photo(): void
    {
        Business::factory()->create(['is_featured' => true]);
        $description = '<script>alert("hero")</script> فضایی برای یک تجربه تازه';
        $first = Business::factory()->create(['is_featured' => true, 'description' => $description]);
        $gallery = $this->photo($first);
        $selected = $this->photo($first);
        $first->featuredPhotos()->attach($gallery, ['position' => 1]);
        $first->update(['hero_media_id' => $selected->id]);
        $second = Business::factory()->create(['is_featured' => true]);
        $secondPhoto = $this->photo($second);
        $second->update(['hero_media_id' => $secondPhoto->id]);

        $response = $this->get(route('home'))->assertOk();
        $featured = $response->viewData('featuredBusiness');
        $this->assertNotNull($featured);
        $this->assertContains($featured->id, [$first->id, $second->id]);
        $this->assertTrue($featured->relationLoaded('heroPhoto'));
        if ($featured->id === $first->id) {
            $this->assertSame($selected->id, $featured->heroPhoto->id);
            $response->assertSee('src="'.route('media.show', $selected).'"', false)
                ->assertSee('href="'.route('businesses.show', $first->slug).'"', false)
                ->assertSee($first->name)->assertSee($description)->assertDontSee($description, false)
                ->assertDontSee(route('media.show', $gallery));
        } else {
            $this->assertSame($secondPhoto->id, $featured->heroPhoto->id);
            $response->assertSee('src="'.route('media.show', $secondPhoto).'"', false)
                ->assertSee('href="'.route('businesses.show', $second->slug).'"', false);
        }
    }

    #[TestWith(['pending', 'published'])]
    #[TestWith(['rejected', 'published'])]
    #[TestWith(['merged', 'published'])]
    #[TestWith(['approved', 'pending'])]
    #[TestWith(['approved', 'hidden'])]
    #[TestWith(['approved', 'removed'])]
    public function test_homepage_excludes_unapproved_businesses_and_unpublished_hero_photos(string $businessStatus, string $mediaStatus): void
    {
        $business = Business::factory()->create(['status' => $businessStatus, 'is_featured' => true]);
        $business->update(['hero_media_id' => $this->photo($business, ['status' => $mediaStatus])->id]);

        $this->get(route('home'))->assertOk()->assertViewHas('featuredBusiness', null);
    }

    public function test_homepage_excludes_a_selected_photo_belonging_to_another_business(): void
    {
        $business = Business::factory()->create(['is_featured' => true]);
        $business->update(['hero_media_id' => $this->photo(Business::factory()->create())->id]);

        $this->get(route('home'))->assertOk()->assertViewHas('featuredBusiness', null);
    }

    #[TestWith(['published', false, true])]
    #[TestWith(['hidden', false, false])]
    #[TestWith(['pending', false, false])]
    #[TestWith(['published', true, false])]
    public function test_review_photos_are_eligible_only_while_the_review_is_public(string $status, bool $deleted, bool $eligible): void
    {
        $business = Business::factory()->create(['is_featured' => true]);
        $review = $this->review($business, $status);
        if ($deleted) {
            $review->delete();
        }
        $business->update(['hero_media_id' => $this->photo($business, ['review_id' => $review->id])->id]);

        $response = $this->get(route('home'))->assertOk();

        $this->assertSame($eligible ? $business->id : null, $response->viewData('featuredBusiness')?->id);
    }

    #[TestWith([PlatformRole::Admin])]
    #[TestWith([PlatformRole::Superadmin])]
    public function test_admin_can_persist_replace_and_clear_homepage_settings_independently_of_gallery(PlatformRole $role): void
    {
        $this->staff($role);
        $previous = Business::factory()->create(['is_featured' => true]);
        $previousPhoto = $this->photo($previous);
        $previous->update(['hero_media_id' => $previousPhoto->id]);
        $business = Business::factory()->create();
        $gallery = $this->photo($business);
        $hero = $this->photo($business);
        $replacement = $this->photo($business);
        $profile = $business->only(['name', 'category_id', 'city', 'address']) + ['featured_media_ids' => [$gallery->id]];

        $this->put(route('admin.businesses.update', $business), $profile + ['is_featured' => '1', 'hero_media_id' => $hero->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => true, 'hero_media_id' => $hero->id]);
        $this->assertDatabaseHas('businesses', ['id' => $previous->id, 'is_featured' => true, 'hero_media_id' => $previousPhoto->id]);
        $this->get(route('home'))->assertOk()->assertViewHas('featuredBusiness', fn (Business $featured): bool => in_array($featured->id, [$business->id, $previous->id], true));
        $this->assertSame($gallery->id, $business->featuredPhotos()->firstOrFail()->id);

        $this->put(route('admin.businesses.update', $business), $profile + ['hero_media_id' => $replacement->id])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => true, 'hero_media_id' => $replacement->id]);

        $this->put(route('admin.businesses.update', $business), $profile + ['is_featured' => '0', 'hero_media_id' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => false, 'hero_media_id' => null]);
        $this->assertDatabaseHas('moderation_history', ['content_id' => (string) $business->id, 'action' => 'profile_update']);
    }

    #[TestWith([['is_featured' => 1], 'hero_media_id'])]
    #[TestWith([['is_featured' => 1, 'hero_media_id' => ''], 'hero_media_id'])]
    #[TestWith([['is_featured' => 'yes'], 'is_featured'])]
    #[TestWith([['hero_media_id' => 'invalid'], 'hero_media_id'])]
    #[TestWith([['hero_media_id' => '00000000-0000-4000-8000-000000000000'], 'hero_media_id'])]
    public function test_invalid_hero_settings_do_not_change_the_profile(array $settings, string $field): void
    {
        $this->staff();
        $previous = Business::factory()->create(['is_featured' => true]);
        $business = Business::factory()->create();

        $this->putJson(route('admin.businesses.update', $business), $settings + $business->only(['name', 'category_id', 'city', 'address']))
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => false, 'hero_media_id' => null]);
        $this->assertDatabaseHas('businesses', ['id' => $previous->id, 'is_featured' => true]);
        $this->assertDatabaseMissing('moderation_history', ['content_id' => (string) $business->id, 'action' => 'profile_update']);
    }

    #[TestWith(['foreign'])]
    #[TestWith(['pending'])]
    #[TestWith(['hidden'])]
    #[TestWith(['hidden_review'])]
    #[TestWith(['deleted_review'])]
    public function test_admin_cannot_select_a_foreign_or_nonpublic_photo(string $kind): void
    {
        $this->staff();
        $business = Business::factory()->create();
        $photoBusiness = $kind === 'foreign' ? Business::factory()->create() : $business;
        $photo = $this->photo($photoBusiness, ['status' => in_array($kind, ['pending', 'hidden'], true) ? $kind : 'published']);
        if (in_array($kind, ['hidden_review', 'deleted_review'], true)) {
            $review = $this->review($business, $kind === 'hidden_review' ? 'hidden' : 'published');
            if ($kind === 'deleted_review') {
                $review->delete();
            }
            $photo->update(['review_id' => $review->id]);
        }

        $this->putJson(route('admin.businesses.update', $business), $business->only(['name', 'category_id', 'city', 'address']) + ['hero_media_id' => $photo->id])
            ->assertUnprocessable()->assertJsonValidationErrors('hero_media_id');

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => false, 'hero_media_id' => null]);
    }

    public function test_admin_cannot_clear_the_photo_while_business_remains_featured(): void
    {
        $this->staff();
        $business = Business::factory()->create(['is_featured' => true]);
        $photo = $this->photo($business);
        $business->update(['hero_media_id' => $photo->id]);

        $this->putJson(route('admin.businesses.update', $business), $business->only(['name', 'category_id', 'city', 'address']) + ['hero_media_id' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('hero_media_id');

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => true, 'hero_media_id' => $photo->id]);
    }

    #[TestWith([['is_featured' => 1]])]
    #[TestWith([['is_featured' => 0]])]
    #[TestWith([['hero_media_id' => '']])]
    public function test_owners_cannot_forge_homepage_settings_even_to_clear_them(array $settings): void
    {
        $owner = User::factory()->create(['mobile_verified_at' => now()]);
        $business = Business::factory()->create(['is_featured' => true]);
        $photo = $this->photo($business);
        $business->update(['hero_media_id' => $photo->id]);
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);

        $this->actingAs($owner)->putJson(route('business.businesses.update', $business), $settings + $business->only(['name', 'category_id', 'city', 'address']))
            ->assertForbidden();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => true, 'hero_media_id' => $photo->id]);
        $this->assertDatabaseMissing('moderation_history', ['content_id' => (string) $business->id, 'action' => 'profile_update']);
    }

    public function test_normal_owner_profile_updates_preserve_admin_homepage_settings(): void
    {
        $owner = User::factory()->create(['mobile_verified_at' => now()]);
        $business = Business::factory()->create(['is_featured' => true]);
        $photo = $this->photo($business);
        $business->update(['hero_media_id' => $photo->id]);
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);

        $this->actingAs($owner)->put(route('business.businesses.update', $business), $business->only(['name', 'category_id', 'city', 'address']) + ['description' => 'معرفی تازه'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'description' => 'معرفی تازه', 'is_featured' => true, 'hero_media_id' => $photo->id]);
    }

    public function test_service_rejects_nonstaff_homepage_settings(): void
    {
        $business = Business::factory()->create();
        $owner = User::factory()->create();

        try {
            app(UpdateBusinessProfile::class)->handle($business, $owner, ['is_featured' => true]);
            $this->fail('Nonstaff homepage settings must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => false]);
    }

    public function test_deleting_selected_media_clears_reference_and_removes_business_from_homepage(): void
    {
        $business = Business::factory()->create(['is_featured' => true]);
        $photo = $this->photo($business);
        $business->update(['hero_media_id' => $photo->id]);

        $photo->delete();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'hero_media_id' => null]);
        $this->get(route('home'))->assertOk()->assertViewHas('featuredBusiness', null);
    }

    #[TestWith([PlatformRole::Admin])]
    #[TestWith([PlatformRole::Superadmin])]
    public function test_staff_edit_form_includes_homepage_controls_and_published_photo_options(PlatformRole $role): void
    {
        $this->staff($role);
        $business = Business::factory()->create(['is_featured' => true]);
        $photo = $this->photo($business);
        $hidden = $this->photo($business, ['status' => 'hidden']);
        $business->update(['hero_media_id' => $photo->id]);

        $this->get(route('admin.businesses.edit', $business))->assertOk()
            ->assertSee('name="is_featured" value="0"', false)
            ->assertSee('name="is_featured" value="1"', false)
            ->assertSee('name="hero_media_id" value=""', false)
            ->assertSee('name="hero_media_id" value="'.$photo->id.'"', false)
            ->assertDontSee('name="hero_media_id" value="'.$hidden->id.'"', false);
    }

    public function test_owner_edit_form_omits_homepage_controls(): void
    {
        $owner = User::factory()->create(['mobile_verified_at' => now()]);
        $business = Business::factory()->create();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);

        $this->actingAs($owner)->get(route('business.businesses.edit', $business))->assertOk()
            ->assertDontSee('name="is_featured"', false)->assertDontSee('name="hero_media_id"', false);
    }

    #[TestWith([PlatformRole::Admin])]
    #[TestWith([PlatformRole::Superadmin])]
    public function test_staff_owner_cannot_change_homepage_settings_through_business_portal(PlatformRole $role): void
    {
        $owner = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => $role]);
        $business = Business::factory()->create();
        $photo = $this->photo($business);
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);

        $this->actingAs($owner)->putJson(route('business.businesses.update', $business), $business->only(['name', 'category_id', 'city', 'address']) + ['is_featured' => 1, 'hero_media_id' => $photo->id])
            ->assertForbidden();

        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'is_featured' => false, 'hero_media_id' => null]);
        $this->assertDatabaseMissing('moderation_history', ['content_id' => (string) $business->id, 'action' => 'profile_update']);
    }

    private function staff(PlatformRole $role = PlatformRole::Admin): void
    {
        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => $role]);
        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]]);
    }

    private function photo(Business $business, array $attributes = []): Media
    {
        return Media::create($attributes + [
            'id' => (string) Str::uuid(), 'client_id' => (string) Str::uuid(), 'user_id' => User::factory()->create()->id,
            'business_id' => $business->id, 'status' => 'published', 'path' => 'full.jpg', 'thumbnail_path' => 'thumb.jpg',
        ]);
    }

    private function review(Business $business, string $status): Review
    {
        return Review::create([
            'business_id' => $business->id, 'user_id' => User::factory()->create()->id,
            'rating' => 4, 'body' => 'تجربه خوب', 'visit_date' => '2026-09-01', 'status' => $status,
        ]);
    }
}
