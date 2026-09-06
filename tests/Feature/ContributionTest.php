<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Business;
use App\Models\Comment;
use App\Models\ContributionDraft;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use App\Notifications\SubmissionUpdated;
use App\Support\BusinessIdentity;
use App\Support\PersianDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContributionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function contributor(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['name' => 'نگار', 'mobile_verified_at' => now()]);
    }

    private function draft(User $user, array $payload = []): ContributionDraft
    {
        $id = (string) Str::uuid();
        $this->actingAs($user)->postJson('/contribution-drafts', ['id' => $id])->assertOk()->assertJsonPath('version', 1)->assertJsonPath('status', 'draft');
        $this->putJson('/contribution-drafts/'.$id, $payload + [
            'version' => 1, 'name' => 'کافه آفتاب', 'city' => 'تهران', 'address' => 'خیابان حافظ، پلاک ۱۲', 'category_id' => 1,
            'with_review' => true, 'rating' => 4, 'body' => 'محیط آرام و برخورد بسیار خوبی داشتند.', 'photo_ids' => [],
        ])->assertOk();

        return ContributionDraft::findOrFail($id);
    }

    private function staff(): User
    {
        $user = $this->contributor(['platform_role' => PlatformRole::Admin]);
        $this->actingAs($user)->withSession(['staff_auth' => ['user_id' => $user->id, 'verified_at' => now()->timestamp]]);

        return $user;
    }

    public function test_combined_submission_is_private_idempotent_and_approval_publishes_rating_and_media(): void
    {
        Storage::fake('local');
        Notification::fake();
        $user = $this->contributor();
        $client = (string) Str::uuid();
        $draft = $this->draft($user, ['photo_ids' => [$client]]);
        $photo = $this->postJson('/contribution-drafts/'.$draft->id.'/photos', ['client_id' => $client, 'photo' => UploadedFile::fake()->image('photo.jpg', 300, 200)])->assertCreated()->json('id');
        $this->postJson('/contribution-drafts/'.$draft->id.'/photos', ['client_id' => $client, 'photo' => UploadedFile::fake()->image('photo.jpg', 300, 200)])->assertOk()->assertJsonPath('id', $photo);

        $result = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->assertJsonPath('status', 'pending')->json();
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->assertExactJson($result);
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('media', 1);
        $this->assertDatabaseCount('business_user', 0);
        $this->get('/account/contributions')->assertOk()->assertSee('کافه آفتاب');
        $business = Business::first();
        $this->actingAs($this->contributor())->get('/media/'.$photo)->assertNotFound();
        $this->get('/businesses/'.$business->slug)->assertNotFound();
        $this->getJson('/businesses/search?query=کافه')->assertJsonCount(0, 'data');
        $this->assertSame(0, $business->reviews()->count());

        $this->staff();
        $this->post('/admin/submissions/'.$business->id, ['action' => 'approve', 'reason' => 'اطلاعات بررسی شد'])->assertRedirect();
        Notification::assertSentTo($user, SubmissionUpdated::class);
        $this->assertSame(1, $business->reviews()->count());
        $this->get('/media/'.$photo)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get('/businesses/'.$business->slug)->assertOk()->assertSee('محیط آرام');
        $this->assertDatabaseHas('moderation_history', ['content_id' => (string) $business->id, 'action' => 'approve']);
    }

    public function test_selected_but_missing_photos_prevent_any_submission(): void
    {
        $draft = $this->draft($this->contributor(), ['photo_ids' => [(string) Str::uuid()]]);
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertUnprocessable()->assertJsonValidationErrors('photo_ids');
        $this->assertDatabaseCount('businesses', 0);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_exact_pending_matches_are_reused_without_exposing_their_content(): void
    {
        $first = $this->draft($this->contributor());
        $this->postJson('/contribution-drafts/'.$first->id.'/submit')->assertOk();
        $second = $this->draft($this->contributor(), ['name' => 'كافه آفتاب', 'address' => 'خیابان حافظ، پلاک ١٢']);
        $this->postJson('/contribution-drafts/'.$second->id.'/submit')->assertOk()->assertJsonPath('status', 'pending');
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseCount('reviews', 2);
        $this->getJson('/businesses/search')->assertJsonCount(0, 'data');
    }

    public function test_fuzzy_match_requires_confirmation_but_separate_branch_can_be_added(): void
    {
        Business::factory()->create(['name' => 'کافه آفتاب', 'normalized_name' => 'کافه آفتاب', 'city' => 'تهران', 'normalized_city' => 'تهران']);
        $draft = $this->draft($this->contributor());
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertUnprocessable()->assertJsonValidationErrors('confirm_distinct');
        $this->putJson('/contribution-drafts/'.$draft->id, $draft->payload + ['version' => 2, 'confirm_distinct' => true])->assertOk();
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk();
        $this->assertDatabaseCount('businesses', 2);
    }

    public function test_repeat_review_returns_edit_path_without_overwriting_and_explicit_edit_preserves_history(): void
    {
        $user = $this->contributor();
        $business = Business::factory()->create();
        $draft = $this->draft($user, ['business_id' => $business->id]);
        $reviewId = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->json('review_id');
        $repeat = $this->draft($user, ['business_id' => $business->id, 'rating' => 2]);
        $this->postJson('/contribution-drafts/'.$repeat->id.'/submit')->assertConflict()->assertJsonPath('edit_url', route('reviews.edit', $reviewId));
        $this->assertDatabaseHas('reviews', ['id' => $reviewId, 'rating' => 4]);
        $this->putJson('/contribution-drafts/'.$repeat->id, $repeat->payload + ['version' => 2, 'edit_review_id' => $reviewId, 'review_version' => 1])->assertOk();
        $this->postJson('/contribution-drafts/'.$repeat->id.'/submit')->assertOk();
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseCount('review_revisions', 1);
        $this->assertSame(2.0, (float) $business->reviews()->avg('rating'));
        $this->delete('/reviews/'.$reviewId)->assertRedirect();
        $this->assertSame(0, $business->reviews()->count());
    }

    public function test_owner_cannot_review_own_business_and_submission_does_not_grant_ownership(): void
    {
        $user = $this->contributor();
        $business = Business::factory()->create();
        $business->owners()->attach($user, ['role' => 'owner', 'approved_at' => now()]);
        $draft = $this->draft($user, ['business_id' => $business->id]);
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_recent_staff_can_publish_a_new_business_without_a_review_or_ownership(): void
    {
        $staff = $this->staff();
        $draft = $this->draft($staff, ['with_review' => false]);
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->assertJsonPath('status', 'published');
        $this->assertDatabaseHas('businesses', ['status' => 'approved']);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('business_user', 0);
    }

    public function test_cross_account_drafts_and_suspended_writes_are_rejected(): void
    {
        $draft = $this->draft($this->contributor());
        $other = $this->contributor();
        $this->actingAs($other)->getJson('/contribution-drafts/'.$draft->id)->assertNotFound();
        $this->postJson('/contribution-drafts', ['id' => $draft->id])->assertNotFound();
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertNotFound();
        $other->suspended_at = now();
        $other->save();
        $this->postJson('/contribution-drafts', ['id' => (string) Str::uuid()])->assertForbidden();
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_stale_draft_updates_are_rejected_and_newer_input_survives(): void
    {
        $draft = $this->draft($this->contributor());
        $this->putJson('/contribution-drafts/'.$draft->id, array_merge($draft->payload, ['version' => 1, 'name' => 'نام قدیمی']))->assertConflict();
        $this->assertSame('کافه آفتاب', $draft->fresh()->payload['name']);
    }

    public function test_malicious_image_is_rejected_and_photo_limit_is_enforced(): void
    {
        Storage::fake('local');
        $draft = $this->draft($this->contributor());
        $this->postJson('/contribution-drafts/'.$draft->id.'/photos', ['client_id' => (string) Str::uuid(), 'photo' => UploadedFile::fake()->createWithContent('attack.jpg', '<svg onload="alert(1)"></svg>')])->assertUnprocessable();
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/contribution-drafts/'.$draft->id.'/photos', ['client_id' => (string) Str::uuid(), 'photo' => UploadedFile::fake()->image('ok.png', 200, 200)])->assertCreated();
        }
        $this->postJson('/contribution-drafts/'.$draft->id.'/photos', ['client_id' => (string) Str::uuid(), 'photo' => UploadedFile::fake()->image('ok.png', 200, 200)])->assertUnprocessable();
        $this->assertDatabaseCount('media', 6);
    }

    public function test_comments_votes_reports_saves_and_owner_replies_persist_with_permissions(): void
    {
        $business = Business::factory()->create();
        $draft = $this->draft($this->contributor(), ['business_id' => $business->id]);
        $review = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->json('review_id');
        $other = $this->contributor();
        $this->actingAs($other)->post('/reviews/'.$review.'/helpful')->assertRedirect();
        $this->post('/reviews/'.$review.'/helpful')->assertRedirect();
        $this->post('/businesses/'.$business->id.'/save')->assertRedirect();
        $this->post('/reviews/'.$review.'/comments', ['body' => 'دیدگاه مفید بود'])->assertRedirect();
        $parent = Comment::first();
        $this->post('/reviews/'.$review.'/comments', ['body' => 'ممنون از توضیح', 'parent_id' => $parent->id])->assertRedirect();
        $this->post('/reviews/'.$review.'/comments', ['body' => 'پاسخ عمیق غیرمجاز', 'parent_id' => Comment::latest('id')->first()->id])->assertNotFound();
        for ($i = 0; $i < 2; $i++) {
            $this->post('/reports', ['content_type' => 'review', 'content_id' => (string) $review, 'reason' => 'نیاز به بررسی دارد'])->assertRedirect();
        }
        $this->put('/reviews/'.$review.'/owner-reply', ['body' => 'از شما سپاسگزاریم'])->assertForbidden();
        $business->owners()->attach($other, ['role' => 'owner', 'approved_at' => now()]);
        $this->put('/reviews/'.$review.'/owner-reply', ['body' => 'از شما سپاسگزاریم'])->assertRedirect();
        $this->assertDatabaseCount('helpful_votes', 1);
        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseCount('saved_businesses', 1);
        $this->assertDatabaseCount('owner_replies', 1);
        $this->get('/reviews/'.$review)->assertOk()->assertSee('ممنون از توضیح');
        $this->actingAs($this->contributor())->delete('/comments/'.$parent->id)->assertNotFound();
        $this->get('/account/contributions')->assertOk()->assertDontSee($business->name);
    }

    public function test_rejection_creates_a_correction_draft_without_breaking_original_idempotency(): void
    {
        Notification::fake();
        $author = $this->contributor();
        $draft = $this->draft($author);
        $result = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->json();
        $this->staff();
        $this->post('/admin/submissions/'.$result['business_id'], ['action' => 'reject', 'reason' => 'آدرس دقیق‌تر لازم است'])->assertRedirect();
        $correction = ContributionDraft::where('status', 'draft')->first();
        $this->assertSame($result['business_id'], $correction->payload['correction_business_id']);
        $this->actingAs($author)->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->assertExactJson($result);
        $this->putJson('/contribution-drafts/'.$correction->id, array_merge($correction->payload, ['version' => 1, 'address' => 'خیابان حافظ، پلاک ۱۴']))->assertOk();
        $this->postJson('/contribution-drafts/'.$correction->id.'/submit')->assertOk()->assertJsonPath('status', 'pending');
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseHas('businesses', ['address' => 'خیابان حافظ، پلاک ۱۴', 'status' => 'pending']);
    }

    public function test_hidden_review_rating_is_removed_and_restoration_respects_author_deletion(): void
    {
        $business = Business::factory()->create();
        $author = $this->contributor();
        $draft = $this->draft($author, ['business_id' => $business->id]);
        $review = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->json('review_id');
        $this->post('/reports', ['content_type' => 'review', 'content_id' => (string) $review, 'reason' => 'بررسی تجربه لازم است'])->assertRedirect();
        $report = DB::table('reports')->first();
        $this->staff();
        $this->post('/admin/reports/'.$report->id, ['action' => 'hide', 'reason' => 'نیاز به بررسی'])->assertRedirect();
        $this->assertSame(0, $business->reviews()->count());
        $this->post('/admin/reports/'.$report->id, ['action' => 'restore', 'reason' => 'محتوا معتبر است'])->assertRedirect();
        $this->assertSame(1, $business->reviews()->count());
        $this->actingAs($author)->delete('/reviews/'.$review)->assertRedirect();
        $this->staff();
        $this->post('/admin/reports/'.$report->id, ['action' => 'restore', 'reason' => 'بازگردانی مدیریت'])->assertRedirect();
        $this->assertSame(0, $business->reviews()->count());
    }

    public function test_jalali_dates_are_strict_and_today_uses_tehran_midnight(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-20 21:00:00', 'UTC'));
        $this->assertSame('2026-03-21', PersianDate::today());
        $this->assertSame('2025-03-20', PersianDate::toGregorian('۱۴۰۳/۱۲/۳۰'));
        $this->assertSame('2026-03-21', PersianDate::toGregorian('١٤٠٥/٠١/٠١'));
        foreach (['1404/12/30', '1405/01/02', '1403/13/01', '1403/07/31'] as $invalid) {
            try {
                PersianDate::toGregorian($invalid);
                $this->fail('Invalid date accepted: '.$invalid);
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame('کیوسک 12', BusinessIdentity::normalize(' كيوسك ۱۲ '));
    }

    public function test_abandoned_draft_cleanup_preserves_submitted_media(): void
    {
        Storage::fake('local');
        $draft = $this->draft($this->contributor());
        $photo = $this->postJson('/contribution-drafts/'.$draft->id.'/photos', ['client_id' => (string) Str::uuid(), 'photo' => UploadedFile::fake()->image('ok.png', 200, 200)])->assertCreated()->json('id');
        $path = Media::find($photo)->path;
        $this->travel(8)->days();
        $this->artisan('contributions:cleanup')->assertSuccessful();
        $this->assertDatabaseMissing('contribution_drafts', ['id' => $draft->id]);
        $this->assertDatabaseMissing('media', ['id' => $photo]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_discarded_drafts_cannot_be_resubmitted_or_revived(): void
    {
        $draft = $this->draft($this->contributor());
        $this->deleteJson('/contribution-drafts/'.$draft->id)->assertOk();
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertStatus(410);
        $this->putJson('/contribution-drafts/'.$draft->id, $draft->payload + ['version' => 2])->assertStatus(410);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_merge_conflict_preserves_existing_review_and_moves_photos_to_private_correction_draft(): void
    {
        Storage::fake('local');
        Notification::fake();
        $author = $this->contributor();
        $target = Business::factory()->create();
        $first = $this->draft($author, ['business_id' => $target->id]);
        $original = $this->postJson('/contribution-drafts/'.$first->id.'/submit')->assertOk()->json('review_id');
        $client = (string) Str::uuid();
        $second = $this->draft($author, ['photo_ids' => [$client], 'rating' => 2]);
        $photo = $this->postJson('/contribution-drafts/'.$second->id.'/photos', ['client_id' => $client, 'photo' => UploadedFile::fake()->image('photo.jpg', 200, 200)])->assertCreated()->json('id');
        $source = $this->postJson('/contribution-drafts/'.$second->id.'/submit')->assertOk()->json('business_id');
        $this->staff();
        $this->get('/admin/submissions/'.$source)->assertOk()->assertSee('محیط آرام');
        $this->post('/admin/submissions/'.$source, ['action' => 'merge', 'target_id' => $target->id, 'reason' => 'این مکان تکراری است'])->assertRedirect();
        $correction = ContributionDraft::where('status', 'draft')->firstOrFail();
        $this->assertSame($original, $correction->payload['edit_review_id']);
        $this->assertDatabaseHas('reviews', ['id' => $original, 'rating' => 4]);
        $this->assertSame(4.0, (float) $target->reviews()->avg('rating'));
        $this->assertDatabaseHas('media', ['id' => $photo, 'contribution_draft_id' => $correction->id, 'business_id' => null, 'review_id' => null]);
        $this->actingAs($this->contributor())->get('/media/'.$photo)->assertNotFound();
        Notification::assertSentTo($author, SubmissionUpdated::class);
    }

    public function test_report_queue_shows_content_and_requires_recent_staff_authentication(): void
    {
        $business = Business::factory()->create();
        $draft = $this->draft($this->contributor(), ['business_id' => $business->id]);
        $review = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->json('review_id');
        $this->post('/reports', ['content_type' => 'review', 'content_id' => (string) $review, 'reason' => 'محتوا بررسی شود'])->assertRedirect();
        $this->get('/admin/reports')->assertForbidden();
        $this->staff();
        $this->get('/admin/reports')->assertOk()->assertSee('محیط آرام و برخورد بسیار خوبی داشتند.');
        $this->withSession(['staff_auth.verified_at' => now()->subHour()->timestamp])->get('/admin/reports')->assertRedirect();
    }

    public function test_review_validation_is_farsi_and_invalid_date_rolls_back_submission(): void
    {
        $draft = $this->draft($this->contributor(), ['visit_date' => '۱۴۰۴/۱۲/۳۰']);
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertUnprocessable()->assertJsonValidationErrors('visit_date');
        $this->assertDatabaseCount('businesses', 0);
        $this->putJson('/contribution-drafts/'.$draft->id, ['version' => 2, 'with_review' => true, 'photo_ids' => [], 'rating' => 9])->assertUnprocessable()->assertJsonPath('errors.rating.0', 'امتیاز باید بین 1 و 5 باشد.');
    }

    public function test_pending_reviews_can_be_edited_by_the_author_while_remaining_private(): void
    {
        $author = $this->contributor();
        $draft = $this->draft($author);
        $result = $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->json();
        $review = Review::findOrFail($result['review_id']);
        $this->get('/reviews/'.$review->id.'/edit')->assertRedirect(route('contribute', ['review' => $review->id]));
        $this->get('/contribute?review='.$review->id)->assertOk()->assertViewHas('initial', fn ($initial) => $initial['body'] === $review->body);
        $edit = $this->draft($author, ['business_id' => $review->business_id, 'edit_review_id' => $review->id, 'review_version' => $review->version, 'rating' => 3]);
        $this->postJson('/contribution-drafts/'.$edit->id.'/submit')->assertOk()->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3, 'status' => 'pending']);
        $this->actingAs($this->contributor())->get('/contribute?review='.$review->id)->assertNotFound();
        $this->get('/reviews/'.$review->id)->assertNotFound();
    }

    public function test_existing_business_review_does_not_require_reentering_business_details(): void
    {
        $business = Business::factory()->create();
        $draft = $this->draft($this->contributor(), ['business_id' => $business->id, 'category_id' => null, 'address' => null, 'name' => null, 'city' => null]);
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk()->assertJsonPath('status', 'published');
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_draft_activity_does_not_consume_the_submission_rate_limit(): void
    {
        $draft = $this->draft($this->contributor());
        for ($i = 0; $i < 20; $i++) {
            $this->getJson('/contribution-drafts/'.$draft->id)->assertOk();
        }
        $this->postJson('/contribution-drafts/'.$draft->id.'/submit')->assertOk();
    }
}
