<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function superadmin(): User
    {
        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => PlatformRole::Superadmin]);
        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]]);

        return $admin;
    }

    public function test_superadmin_can_provision_staff_and_view_the_audit_record(): void
    {
        $this->superadmin();

        $this->post(route('admin.users.store'), ['name' => 'همکار محتوا', 'mobile' => '۰۹۱۲۳۴۵۶۷۸۹', 'platform_role' => 'admin'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['name' => 'همکار محتوا', 'mobile' => '+989123456789', 'platform_role' => 'admin']);
        $this->get(route('admin.audit-log.index'))->assertSee('staff_provisioned')->assertSee('همکار محتوا');
    }

    public function test_superadmin_can_suspend_a_user_but_cannot_suspend_self(): void
    {
        $admin = $this->superadmin();
        $user = User::factory()->create(['mobile_verified_at' => now()]);

        $this->put(route('admin.users.update', $user), ['platform_role' => 'user', 'status' => 'suspended'])->assertRedirect();
        $this->assertNotNull($user->fresh()->suspended_at);

        $this->put(route('admin.users.update', $admin), ['platform_role' => 'admin', 'status' => 'suspended'])->assertUnprocessable();
        $this->assertSame(PlatformRole::Superadmin, $admin->fresh()->platform_role);
    }

    public function test_admin_cannot_access_superadmin_management_areas(): void
    {
        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => PlatformRole::Admin]);
        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]]);

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.taxonomy.index'))->assertForbidden();
        $this->get(route('admin.audit-log.index'))->assertForbidden();
    }

    public function test_superadmin_can_add_and_retire_a_city_and_category(): void
    {
        $this->superadmin();

        $this->post(route('admin.taxonomy.cities.store'), ['name' => 'یزد', 'latitude' => 31.89, 'longitude' => 54.36])->assertRedirect();
        $city = City::where('name', 'یزد')->firstOrFail();
        $this->put(route('admin.taxonomy.cities.update', $city), ['name' => 'یزد', 'latitude' => 31.89, 'longitude' => 54.36, 'position' => 20, 'is_active' => false])->assertRedirect();

        $this->post(route('admin.taxonomy.categories.store'), ['name' => 'کتاب‌فروشی'])->assertRedirect();
        $category = Category::where('name', 'کتاب‌فروشی')->firstOrFail();
        $this->put(route('admin.taxonomy.categories.update', $category), ['name' => 'کتاب‌فروشی', 'position' => 20, 'is_active' => false])->assertRedirect();

        $this->assertFalse($city->fresh()->is_active);
        $this->assertFalse($category->fresh()->is_active);
        $this->get(route('admin.taxonomy.index'))->assertSee('کتاب‌فروشی')->assertSee('یزد');
    }
}
