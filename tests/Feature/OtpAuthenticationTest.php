<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Exceptions\KavenegarSmsException;
use App\Models\Business;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Services\KavenegarApiFactory;
use App\Services\KavenegarSmsSender;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kavenegar\Exceptions\HttpException as KavenegarHttpException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OtpAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function captureSms(): object
    {
        $capture = new class(Mockery::mock(KavenegarApiFactory::class)) extends KavenegarSmsSender
        {
            public array $messages = [];

            public function send(string $mobile, #[\SensitiveParameter] string $code): void
            {
                $this->messages[] = compact('mobile', 'code');
            }
        };
        $this->app->instance(KavenegarSmsSender::class, $capture);

        return $capture;
    }

    public static function portals(): array
    {
        return [[''], ['/business'], ['/admin']];
    }

    #[DataProvider('portals')]
    public function test_all_portals_render_farsi_forms_and_require_a_challenge(string $prefix): void
    {
        $this->get($prefix.'/login')->assertOk()->assertSee('lang="fa" dir="rtl"', false)->assertSee('شماره موبایل')->assertSee('rel="icon" type="image/png" href="'.asset('images/logo/red-bookmark.png').'"', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->get($prefix.'/verify')->assertRedirect($prefix.'/login');
        $this->post($prefix.'/verify', ['code' => '12345'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public static function mobiles(): array
    {
        return [['۰۹۱۲۳۴۵۶۷۸۹'], ['٠٩١٢٣٤٥٦٧٨٩'], ['09123456789'], ['+98 912 345 6789'], ['00989123456789'], ['989123456789'], ['9123456789']];
    }

    #[DataProvider('mobiles')]
    public function test_mobile_formats_resolve_to_one_canonical_identity(string $mobile): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => $mobile])->assertRedirect('/verify');
        $this->assertSame('+989123456789', $sms->messages[0]['mobile']);
        $this->assertMatchesRegularExpression('/^[0-9]{5}$/D', $sms->messages[0]['code']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_signup_verifies_phone_without_trusting_privilege_fields_and_logs_out(): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789', 'platform_role' => 'superadmin'])->assertRedirect('/verify');
        $code = $sms->messages[0]['code'];
        $challenge = OtpChallenge::firstOrFail();
        $this->assertNotSame($code, $challenge->code_hash);
        $this->get('/verify')->assertOk()->assertDontSee($code)->assertSee('autocomplete="one-time-code"', false);
        $persianCode = strtr($code, array_combine(str_split('0123456789'), preg_split('//u', '۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY)));
        $oldSession = session()->getId();
        $this->post('/verify', ['code' => $persianCode, 'platform_role' => 'superadmin'])->assertRedirect('/account');
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(PlatformRole::User, $user->platform_role);
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertNull($user->email);
        $this->assertNull($user->password);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->get('/account')->assertOk();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_existing_user_is_reused_and_code_cannot_be_replayed(): void
    {
        $sms = $this->captureSms();
        $user = User::factory()->create(['mobile' => '+989123456789']);
        $this->post('/login', ['mobile' => '09123456789']);
        $binding = session('otp_binding');
        $id = session('otp.public');
        $code = $sms->messages[0]['code'];
        $this->post('/verify', ['code' => $code])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->post('/logout');
        $this->withSession(['otp_binding' => $binding, 'otp' => ['public' => $id]])->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_codes_are_bound_to_both_browser_session_and_portal(): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $id = session('otp.public');
        $binding = session('otp_binding');
        $code = $sms->messages[0]['code'];
        $this->withSession(['otp' => ['public' => $id, 'admin' => $id], 'otp_binding' => $binding])->post('/admin/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->withSession(['otp_binding' => 'another-browser'])->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_wrong_codes_lock_the_challenge_and_are_not_flashed(): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $code = $sms->messages[0]['code'];
        $wrong = $code === '00000' ? '11111' : '00000';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/verify')->post('/verify', ['code' => $wrong])->assertSessionHasErrors('code')->assertSessionMissing('_old_input.code');
        }
        $this->post('/verify', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertSame(5, OtpChallenge::firstOrFail()->attempts);
    }

    public function test_codes_expire_at_the_deadline(): void
    {
        $this->freezeTime();
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $this->travel(180)->seconds();
        $this->post('/verify', ['code' => $sms->messages[0]['code']])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_resend_enforces_cooldown_and_invalidates_the_previous_challenge(): void
    {
        $this->freezeTime();
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $old = OtpChallenge::firstOrFail();
        $binding = session('otp_binding');
        $this->from('/verify')->post('/resend', ['mobile' => '09999999999'])->assertSessionHasErrors('mobile');
        $this->assertCount(1, $sms->messages);
        $this->travel(61)->seconds();
        $this->post('/resend', ['mobile' => '09999999999'])->assertRedirect('/verify');
        $newId = session('otp.public');
        $this->assertSame('+989123456789', $sms->messages[1]['mobile']);
        $this->assertNotNull($old->fresh()->consumed_at);
        $this->withSession(['otp' => ['public' => $old->id], 'otp_binding' => $binding])->post('/verify', ['code' => $sms->messages[0]['code']])->assertSessionHasErrors('code');
        $this->withSession(['otp' => ['public' => $newId], 'otp_binding' => $binding])->post('/verify', ['code' => $sms->messages[1]['code']])->assertRedirect('/account');
    }

    public function test_phone_send_limit_is_scoped_to_each_portal(): void
    {
        $this->freezeTime();
        $sms = $this->captureSms();
        for ($send = 0; $send < 5; $send++) {
            $this->post('/login', ['mobile' => '09123456789'])->assertRedirect('/verify');
            $this->travel(61)->seconds();
        }
        $this->post('/business/login', ['mobile' => '+989123456789'])->assertRedirect('/business/verify');
        $this->assertCount(6, $sms->messages);
    }

    public function test_staff_user_login_grants_remembered_admin_access(): void
    {
        $sms = $this->captureSms();
        $user = User::factory()->create([
            'mobile' => '+989123456789',
            'mobile_verified_at' => now(),
            'platform_role' => PlatformRole::Superadmin,
        ]);

        $this->post('/login', ['mobile' => '09123456789'])->assertRedirect('/verify');
        $this->post('/verify', ['code' => $sms->messages[0]['code']])->assertRedirect('/account');

        $this->assertAuthenticatedAs($user);
        $this->get('/admin/dashboard')->assertOk();
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_ip_rate_limit_is_scoped_to_each_portal(): void
    {
        $sms = $this->captureSms();
        for ($send = 0; $send < 5; $send++) {
            $this->post('/login', ['mobile' => '0912345678'.$send])->assertRedirect('/verify');
        }
        $this->post('/business/login', ['mobile' => '09123456789'])->assertRedirect('/business/verify');
        $this->assertCount(6, $sms->messages);
    }

    public function test_business_signup_does_not_grant_ownership_or_staff_access(): void
    {
        $sms = $this->captureSms();
        $this->post('/business/login', ['mobile' => '09123456789', 'role' => 'owner', 'business_id' => 1])->assertRedirect('/business/verify');
        $this->post('/business/verify', ['code' => $sms->messages[0]['code']])->assertRedirect('/business/dashboard');
        $this->get('/business/dashboard')->assertOk()
            ->assertSee('مدیریت کسب‌وکارها')
            ->assertSee('کسب‌وکارهای متصل')
            ->assertSee('هنوز کسب‌وکاری به حساب شما متصل نیست')
            ->assertSee('درخواست مالکیت');
        $this->assertDatabaseCount('business_user', 0);
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_account_page_explains_the_available_personal_actions(): void
    {
        $user = User::factory()->create(['mobile_verified_at' => now(), 'name' => 'نگار رضایی']);

        $this->actingAs($user)->get('/account')->assertOk()
            ->assertSee('فضای شخصی شما در کیوسک')
            ->assertSee('کارهایی که می‌توانید انجام دهید')
            ->assertSee('مشارکت‌های من')
            ->assertSee('کشف مکان‌های شهر')
            ->assertSee('بخش کسب‌وکارها')
            ->assertSee('مشخصات حساب');
    }

    public function test_business_portal_only_shows_approved_ownerships_of_current_user(): void
    {
        $user = User::factory()->create(['mobile' => '+989123456789', 'mobile_verified_at' => now()]);
        $other = User::factory()->create(['mobile' => '+989123456788', 'mobile_verified_at' => now()]);
        foreach ([['کافه خودم', $user, now()], ['کافه دیگران', $other, now()], ['مالکیت تأییدنشده', $user, null]] as [$name, $owner, $approved]) {
            $business = new Business;
            $business->name = $name;
            $business->save();
            $business->owners()->attach($owner, ['role' => 'owner', 'approved_at' => $approved]);
        }
        $this->actingAs($user)->get('/business/dashboard')->assertOk()
            ->assertSee('مدیریت کسب‌وکارها')
            ->assertSee('کافه خودم')
            ->assertSee('مدیریت اطلاعات')
            ->assertDontSee('کافه دیگران')
            ->assertDontSee('مالکیت تأییدنشده');
    }

    public static function staffRoles(): array
    {
        return [[PlatformRole::Admin], [PlatformRole::Superadmin]];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_login_persists_until_their_permission_is_revoked(PlatformRole $role): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['mobile' => '+989123456789', 'mobile_verified_at' => now(), 'platform_role' => $role]);
        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->travel(1800)->seconds();
        $this->get('/admin/dashboard')->assertOk();
        $user->platform_role = PlatformRole::User;
        $user->save();
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_staff_login_does_not_register_unknown_numbers_or_send_to_public_users(): void
    {
        $sms = $this->captureSms();
        $this->post('/admin/login', ['mobile' => '09123456789'])->assertRedirect('/admin/verify');
        User::factory()->create(['mobile' => '+989123456788']);
        $this->post('/admin/login', ['mobile' => '09123456788'])->assertRedirect('/admin/verify');
        $this->assertCount(0, $sms->messages);
        $this->assertDatabaseCount('users', 1);
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_staff_revocation_after_sending_prevents_login(): void
    {
        $sms = $this->captureSms();
        $user = User::factory()->create(['mobile' => '+989123456789', 'platform_role' => PlatformRole::Admin]);
        $this->post('/admin/login', ['mobile' => '09123456789']);
        $user->platform_role = PlatformRole::User;
        $user->save();
        $this->post('/admin/verify', ['code' => $sms->messages[0]['code']])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    #[DataProvider('staffRoles')]
    public function test_admin_dashboard_shows_moderation_work_and_role_aware_portals(PlatformRole $role): void
    {
        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => $role]);
        Business::factory()->create(['status' => 'pending']);
        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('داشبورد')
            ->assertSee('در انتظار بررسی')
            ->assertSee('پرتال کاربر')
            ->assertSee('پرتال مدیریت');
    }

    public function test_navigation_only_shows_the_business_portal_to_approved_owners_and_dashboard_on_admin_inner_pages(): void
    {
        $user = User::factory()->create(['mobile_verified_at' => now()]);
        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('پرتال کسب‌وکار');

        $business = Business::factory()->create();
        $business->owners()->attach($user, ['role' => 'owner', 'approved_at' => now()]);
        $this->get('/')->assertOk()->assertSee('پرتال کسب‌وکار');

        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => PlatformRole::Admin]);
        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->get('/admin/submissions')
            ->assertOk()
            ->assertSee('داشبورد')
            ->assertSee(route('admin.dashboard'), false);
    }

    public function test_normal_user_nav_has_a_profile_menu_with_avatar_account_and_logout(): void
    {
        $user = User::factory()->create(['name' => 'نگار رضایی', 'mobile_verified_at' => now()]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('نگار رضایی')
            ->assertSee('باز کردن منوی حساب کاربری')
            ->assertSee('حساب کاربری')
            ->assertSee('خروج از حساب')
            ->assertSee(route('account'), false)
            ->assertSee(route('logout'), false);
    }

    public function test_admin_sidebar_has_a_logout_action(): void
    {
        $admin = User::factory()->create(['mobile_verified_at' => now(), 'platform_role' => PlatformRole::Admin]);

        $this->actingAs($admin)->withSession(['staff_auth' => ['user_id' => $admin->id, 'verified_at' => now()->timestamp]])
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('خروج از حساب')
            ->assertSee(route('logout'), false);
    }

    public function test_suspension_blocks_issued_codes_and_existing_sessions(): void
    {
        $sms = $this->captureSms();
        $user = User::factory()->create(['mobile' => '+989123456789', 'mobile_verified_at' => now()]);
        $this->post('/login', ['mobile' => '09123456789']);
        $user->suspended_at = now();
        $user->save();
        $this->post('/verify', ['code' => $sms->messages[0]['code']])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->actingAs($user)->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }

    public static function invalidMobiles(): array
    {
        return [[''], ['+12025550123'], ['02112345678'], ['091234567890'], ['0912abcdefg'], [['09123456789']]];
    }

    #[DataProvider('invalidMobiles')]
    public function test_invalid_mobile_input_is_rejected_in_farsi(mixed $mobile): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => $mobile])->assertSessionHasErrors('mobile');
        $this->assertCount(0, $sms->messages);
    }

    public function test_malformed_codes_are_rejected_without_flashing_them(): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $this->post('/verify', ['code' => '1234'])->assertSessionHasErrors(['code' => 'کد ۵ رقمی پیامک‌شده را کامل وارد کنید.'])->assertSessionMissing('_old_input.code');
        $this->assertGuest();
    }

    public function test_staff_provisioning_is_explicit_and_still_requires_mobile_verification(): void
    {
        $this->artisan('kioosk:staff', ['mobile' => '۰۹۱۲۳۴۵۶۷۸۹', '--role' => 'superadmin', '--name' => 'مدیر کیوسک'])->assertSuccessful();
        $user = User::firstOrFail();
        $this->assertSame(PlatformRole::Superadmin, $user->platform_role);
        $this->assertNull($user->mobile_verified_at);
        $this->artisan('kioosk:staff', ['mobile' => 'invalid'])->assertFailed();
    }

    public function test_account_names_are_escaped(): void
    {
        $user = User::factory()->create(['mobile' => '+989123456789', 'mobile_verified_at' => now(), 'name' => '<script>alert(1)</script>']);
        $this->actingAs($user)->get('/account')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_sms_failure_does_not_leave_a_usable_challenge(): void
    {
        config()->set('otp.log_failed_code', false);
        $this->mock(KavenegarSmsSender::class)->shouldReceive('send')->once()->andThrow(new KavenegarHttpException('transport failed', 28));
        Log::shouldReceive('error')->once()->with('OTP delivery failed', [
            'exception_type' => KavenegarHttpException::class,
            'transport_code' => 28,
        ]);
        $this->post('/login', ['mobile' => '09123456789'])->assertSessionHasErrors(['mobile' => 'ارسال کد انجام نشد. کمی بعد دوباره تلاش کنید.'])->assertSessionMissing('otp.public');
        $this->assertDatabaseCount('otp_challenges', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_failed_sms_can_log_a_usable_fallback_code_when_enabled(): void
    {
        config()->set('otp.log_failed_code', true);
        $fallbackCode = null;
        $this->mock(KavenegarSmsSender::class)->shouldReceive('send')->once()->andThrow(new KavenegarHttpException('Could not resolve host', 6));
        Log::shouldReceive('error')->once()->with('OTP delivery failed', [
            'exception_type' => KavenegarHttpException::class,
            'transport_code' => 6,
        ]);
        Log::shouldReceive('warning')->once()->with('OTP delivery fallback code', Mockery::on(function (array $context) use (&$fallbackCode): bool {
            $fallbackCode = $context['code'] ?? null;

            return $context['mobile'] === '+98********89'
                && is_string($fallbackCode)
                && preg_match('/^[0-9]{5}$/D', $fallbackCode) === 1;
        }));

        $this->post('/login', ['mobile' => '09123456789'])->assertRedirect('/verify');

        $this->assertDatabaseCount('otp_challenges', 1);
        $this->post('/verify', ['code' => $fallbackCode])->assertRedirect('/account');
        $this->assertAuthenticated();
    }

    public function test_sms_provider_failure_logs_only_its_safe_status(): void
    {
        config()->set('otp.log_failed_code', false);
        $this->mock(KavenegarSmsSender::class)->shouldReceive('send')->once()->andThrow(new KavenegarSmsException('provider response', 424));
        Log::shouldReceive('error')->once()->with('OTP delivery failed', [
            'exception_type' => KavenegarSmsException::class,
            'provider_status' => 424,
        ]);

        $this->post('/login', ['mobile' => '09123456789'])->assertSessionHasErrors('mobile');

        $this->assertDatabaseCount('otp_challenges', 0);
    }

    public function test_sms_php_error_is_logged_without_otp_configuration_or_mobile_secrets(): void
    {
        config()->set('otp.log_failed_code', false);
        config()->set('services.kavenegar.api_key', 'secret-api-key');
        $this->mock(KavenegarSmsSender::class)->shouldReceive('send')->once()->andReturnUsing(
            fn (string $mobile, string $code) => throw new \Error("Failure for {$mobile} using {$code} and secret-api-key"),
        );
        Log::shouldReceive('error')->once()->with('OTP delivery failed', [
            'exception_type' => \Error::class,
            'error_message' => 'Failure for [REDACTED] using [REDACTED] and [REDACTED]',
        ]);

        $this->post('/login', ['mobile' => '09123456789'])->assertSessionHasErrors('mobile');

        $this->assertDatabaseCount('otp_challenges', 0);
    }

    public function test_new_codes_do_not_reset_the_phone_brute_force_budget(): void
    {
        $this->freezeTime();
        $sms = $this->captureSms();
        foreach (['', '/business'] as $prefix) {
            $this->post($prefix.'/login', ['mobile' => '09123456789']);
            $code = $sms->messages[array_key_last($sms->messages)]['code'];
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->post($prefix.'/verify', ['code' => $code === '00000' ? '11111' : '00000'])->assertSessionHasErrors('code');
            }
            $this->travel(61)->seconds();
        }
        $this->post('/login', ['mobile' => '09123456789'])->assertRedirect('/verify');
        $this->post('/verify', ['code' => $sms->messages[2]['code']])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_hourly_ip_send_limit_is_scoped_to_each_portal(): void
    {
        $this->freezeTime();
        $sms = $this->captureSms();
        for ($send = 0; $send < 20; $send++) {
            $this->post('/login', ['mobile' => '091234567'.str_pad((string) $send, 2, '0', STR_PAD_LEFT)])->assertRedirect('/verify');
            $this->travel(61)->seconds();
        }
        $this->post('/business/login', ['mobile' => '09123456888'])->assertRedirect('/business/verify');
        $this->assertCount(21, $sms->messages);
    }

    public function test_request_forgery_is_rejected_for_send_verify_and_logout(): void
    {
        $this->app['env'] = 'local';
        foreach (['/login', '/verify', '/logout', '/business/login', '/admin/login'] as $endpoint) {
            $this->post($endpoint, ['mobile' => '09123456789', 'code' => '12345'])->assertStatus(419)->assertSee('نشست شما منقضی شده است');
        }
        $this->assertDatabaseCount('otp_challenges', 0);
    }

    public function test_logout_discards_pending_codes_and_staff_confirmation(): void
    {
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $this->post('/logout')->assertRedirect('/')->assertSessionMissing('otp')->assertSessionMissing('otp_binding')->assertSessionMissing('staff_auth');
        $this->post('/verify', ['code' => $sms->messages[0]['code']])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_expired_challenges_are_pruned_without_removing_active_ones(): void
    {
        $this->freezeTime();
        $sms = $this->captureSms();
        $this->post('/login', ['mobile' => '09123456789']);
        $this->travel(2)->days();
        $this->post('/login', ['mobile' => '09123456788']);
        $this->artisan('model:prune', ['--model' => [OtpChallenge::class]])->assertSuccessful();
        $this->assertDatabaseCount('otp_challenges', 1);
        $this->assertDatabaseHas('otp_challenges', ['mobile' => '+989123456788']);
    }

    public function test_inline_contribution_otp_rotates_csrf_and_returns_to_draft_without_exposing_phone(): void
    {
        $sms = $this->captureSms();
        $this->get('/login?contribute=1')->assertOk();
        $oldToken = session()->token();
        $this->postJson('/login', ['mobile' => '۰۹۱۲۳۴۵۶۷۸۹'])->assertOk()->assertJsonPath('resend_after', 60);
        $response = $this->postJson('/verify', ['code' => $sms->messages[0]['code']])->assertOk()->assertJsonPath('redirect', '/contribute')->assertJsonPath('needs_display_name', true);
        $this->assertNotSame($oldToken, $response->json('csrf_token'));
        $this->assertAuthenticated();
        $response->assertDontSee('989123456789');
        $this->postJson('/contribution-drafts', ['id' => (string) Str::uuid()])->assertOk();
    }
}
