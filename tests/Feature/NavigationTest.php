<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_homepage_categories_link_to_discovery_and_preserve_city(): void
    {
        $response = $this->get('/?query=cafe&city='.urlencode('تهران').'&category=1&page=2');

        $response->assertOk()
            ->assertSee(route('discovery', ['city' => 'تهران', 'category' => 2]))
            ->assertSee('action="'.route('discovery').'"', false)
            ->assertSee('value="cafe"', false);
    }

    public function test_unauthenticated_navigation_ends_with_an_icon_only_mobile_login_option(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('>ورود</a>', false)
            ->assertSee('data-mobile-login', false)
            ->assertSee('aria-label="ورود"', false)
            ->assertDontSee('>ورود</span>', false)
            ->assertDontSee('aria-label="تماس با ما"', false);
    }

    public function test_admin_preview_has_a_breadcrumb_back_to_the_submission_queue(): void
    {
        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => PlatformRole::Admin]);
        $business = Business::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->get(route('admin.submissions.show', $business));

        $response->assertOk()->assertSee('aria-label="مسیر صفحه"', false)
            ->assertSeeInOrder(['خانه', 'پرتال مدیریت', 'مشارکت‌ها', $business->name])
            ->assertSee(route('admin.submissions'), false)
            ->assertSee('aria-current="location"', false);
    }

    public function test_account_page_keeps_the_shared_navigation_and_contribution_link(): void
    {
        $user = User::factory()->create(['mobile' => '+989123456789', 'mobile_verified_at' => now()]);

        $this->actingAs($user)->get(route('account'))->assertOk()
            ->assertSee('data-profile-menu', false)
            ->assertSee('aria-label="مسیر صفحه"', false)
            ->assertSee(route('contribute'), false)
            ->assertSee(route('contributions.index'), false)
            ->assertDontSee(route('admin.dashboard'), false);
    }
}
