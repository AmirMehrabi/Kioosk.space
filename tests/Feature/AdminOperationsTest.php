<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\Category;
use App\Models\Business;
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
        $this->get(route('admin.cities.index'))->assertForbidden();
        $this->get(route('admin.categories.index'))->assertForbidden();
        $this->get(route('admin.audit-log.index'))->assertForbidden();
    }

    public function test_superadmin_can_add_and_retire_a_city_and_category(): void
    {
        $this->superadmin();

        $this->post(route('admin.cities.store'), ['name' => 'یزد', 'latitude' => 31.89, 'longitude' => 54.36])->assertRedirect();
        $city = City::where('name', 'یزد')->firstOrFail();
        $this->put(route('admin.cities.update', $city), ['name' => 'یزد', 'latitude' => 31.89, 'longitude' => 54.36, 'position' => 20, 'is_active' => false])->assertRedirect();

        $this->post(route('admin.categories.store'), ['name' => 'کتاب‌فروشی'])->assertRedirect();
        $category = Category::where('name', 'کتاب‌فروشی')->firstOrFail();
        $this->put(route('admin.categories.update', $category), ['name' => 'کتاب‌فروشی', 'position' => 20, 'is_active' => false])->assertRedirect();

        $this->assertFalse($city->fresh()->is_active);
        $this->assertFalse($category->fresh()->is_active);
        $this->get(route('admin.cities.edit', $city))->assertSee('یزد');
        $this->get(route('admin.categories.edit', $category))->assertSee('کتاب‌فروشی');
    }

    public function test_superadmin_can_delete_unused_taxonomy_records_but_not_records_used_by_businesses(): void
    {
        $this->superadmin();
        $city = City::create(['name' => 'قم', 'normalized_name' => 'قم', 'is_active' => true, 'position' => 10]);
        $category = Category::create(['name' => 'هنر', 'is_active' => true, 'position' => 10]);

        $this->delete(route('admin.cities.destroy', $city))->assertRedirect(route('admin.cities.index'));
        $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        $usedCity = City::firstOrCreate(['name' => 'تهران'], ['normalized_name' => 'تهران', 'is_active' => true, 'position' => 10]);
        $usedCategory = Category::firstOrCreate(['name' => 'رستوران'], ['is_active' => true, 'position' => 10]);
        Business::factory()->create(['city' => $usedCity->name, 'category_id' => $usedCategory->id]);

        $this->delete(route('admin.cities.destroy', $usedCity))->assertUnprocessable();
        $this->delete(route('admin.categories.destroy', $usedCategory))->assertUnprocessable();
    }
}
