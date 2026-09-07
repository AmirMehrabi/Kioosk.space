<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Notifications\OwnershipClaimUpdated;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessClaimTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function verifiedUser(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['mobile_verified_at' => now()]);
    }

    public function test_verified_user_can_submit_private_proof_but_only_once_while_open(): void
    {
        Storage::fake('local');
        $claimant = $this->verifiedUser();
        $business = Business::factory()->create();
        $payload = ['business_id' => $business->id, 'note' => 'این فروشگاه متعلق به من است و جواز فعالیت را پیوست کرده‌ام.', 'proofs' => [UploadedFile::fake()->image('license.jpg', 500, 400)]];

        $this->actingAs($claimant)->post(route('business.claims.store'), $payload)->assertRedirect(route('business.claims.index'));
        $claim = BusinessClaim::with('proofs')->firstOrFail();
        Storage::disk('local')->assertExists($claim->proofs->first()->path);
        $this->get(route('business.claims.proofs.show', [$claim, $claim->proofs->first()]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $payload['proofs'] = [UploadedFile::fake()->image('second.jpg', 500, 400)];
        $this->post(route('business.claims.store'), $payload)->assertSessionHasErrors('business_id');
        $this->assertDatabaseCount('business_claims', 1);
        $this->actingAs($this->verifiedUser())->get(route('business.claims.proofs.show', [$claim, $claim->proofs->first()]))->assertNotFound();
    }

    public function test_admin_approval_grants_ownership_audits_and_notifies(): void
    {
        Notification::fake();
        $claimant = $this->verifiedUser();
        $business = Business::factory()->create();
        $claim = BusinessClaim::factory()->create(['business_id' => $business->id, 'user_id' => $claimant->id, 'open_key' => $business->id.':'.$claimant->id]);
        $admin = $this->verifiedUser(['platform_role' => PlatformRole::Admin]);

        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]]);
        $this->get(route('admin.claims.index'))->assertOk()->assertSee($business->name);
        $this->post(route('admin.claims.update', $claim), ['action' => 'approve', 'reason' => 'مدارک مالکیت با اطلاعات ثبتی تطبیق دارد.'])->assertRedirect();

        $this->assertDatabaseHas('business_user', ['business_id' => $business->id, 'user_id' => $claimant->id, 'role' => 'owner']);
        $this->assertDatabaseHas('business_claims', ['id' => $claim->id, 'status' => 'approved', 'open_key' => null, 'decided_by' => $admin->id]);
        $this->assertDatabaseHas('moderation_history', ['content_type' => 'business_claim', 'content_id' => (string) $claim->id, 'action' => 'approve']);
        Notification::assertSentTo($claimant, OwnershipClaimUpdated::class);
        $this->actingAs($claimant)->get(route('business.businesses.edit', $business))->assertOk();
    }

    public function test_owner_and_unverified_user_cannot_claim(): void
    {
        $business = Business::factory()->create();
        $owner = $this->verifiedUser();
        $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => now()]);
        $payload = ['business_id' => $business->id, 'note' => 'مدارک کامل مالکیت این مجموعه را برای بررسی ارسال کرده‌ام.', 'proofs' => [UploadedFile::fake()->image('proof.jpg', 300, 300)]];

        $this->actingAs($owner)->post(route('business.claims.store'), $payload)->assertUnprocessable();
        $this->actingAs(User::factory()->create())->post(route('business.claims.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('business_claims', 0);
    }
}
