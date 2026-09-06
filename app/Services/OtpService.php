<?php

namespace App\Services;

use App\Enums\Portal;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private KavenegarSmsSender $sms) {}

    public function current(Request $request, Portal $portal): ?OtpChallenge
    {
        $id = $request->session()->get('otp.'.$portal->value);
        $binding = $request->session()->get('otp_binding');
        if (! is_string($id) || ! is_string($binding)) {
            return null;
        }

        return OtpChallenge::whereKey($id)->where('portal', $portal->value)
            ->where('binding_hash', hash('sha256', $binding))->whereNull('consumed_at')->first();
    }

    public function send(Request $request, Portal $portal, string $mobile): OtpChallenge
    {
        $key = $this->key($mobile);
        try {
            return Cache::lock('otp:phone-lock:'.$key, 10)->block(3, function () use ($request, $portal, $mobile, $key) {
                return Cache::lock('otp:ip-lock:'.$this->key($request->ip() ?? ''), 10)->block(3, function () use ($request, $portal, $mobile, $key) {
                    $limits = [
                        ['otp:cooldown:'.$key, 1, config('otp.resend_seconds')],
                        ['otp:send-phone:'.$key, config('otp.phone_hourly_limit'), 3600],
                        ['otp:send-ip:'.$this->key($request->ip() ?? ''), config('otp.ip_hourly_limit'), 3600],
                    ];
                    foreach ($limits as [$limit, $maximum, $seconds]) {
                        if (RateLimiter::tooManyAttempts($limit, $maximum)) {
                            throw ValidationException::withMessages(['mobile' => 'درخواست‌های شما بیش از حد مجاز است. کمی صبر کنید و دوباره تلاش کنید.']);
                        }
                    }
                    foreach ($limits as [$limit, $maximum, $seconds]) {
                        RateLimiter::hit($limit, $seconds);
                    }
                    $binding = $request->session()->get('otp_binding', Str::random(64));
                    $request->session()->put('otp_binding', $binding);
                    $code = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                    $challenge = DB::transaction(function () use ($request, $portal, $mobile, $binding, $code) {
                        $old = $this->current($request, $portal);
                        if ($old) {
                            $old->consumed_at = now();
                            $old->save();
                        }
                        $challenge = new OtpChallenge;
                        $challenge->id = (string) Str::uuid();
                        $challenge->mobile = $mobile;
                        $challenge->portal = $portal->value;
                        $challenge->binding_hash = hash('sha256', $binding);
                        $challenge->code_hash = $this->digest($challenge->id, $code);
                        $challenge->expires_at = now()->addSeconds(config('otp.ttl_seconds'));
                        $challenge->attempts = 0;
                        $challenge->save();
                        $user = User::where('mobile', $mobile)->first();
                        // The same response is returned for unavailable or non-staff accounts.
                        if ((! $user || ! $user->suspended_at) && ($portal !== Portal::Admin || $user?->hasStaffAccess())) {
                            try {
                                $this->sms->send($mobile, $code);
                            } catch (\Throwable $exception) {
                                // Transport exceptions may contain the SMS body; never report them verbatim.
                                Log::error('OTP delivery failed', ['exception_type' => $exception::class]);
                                throw ValidationException::withMessages(['mobile' => 'ارسال کد انجام نشد. کمی بعد دوباره تلاش کنید.']);
                            }
                        }

                        return $challenge;
                    });
                    $request->session()->put('otp.'.$portal->value, $challenge->id);

                    return $challenge;
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['mobile' => 'درخواست دیگری در حال پردازش است. چند لحظه دیگر تلاش کنید.']);
        }
    }

    public function verify(Request $request, Portal $portal, #[\SensitiveParameter] string $code): User
    {
        $current = $this->current($request, $portal);
        if (! $current) {
            $this->invalid();
        }
        $key = $this->key($current->mobile);
        try {
            $user = Cache::lock('otp:phone-lock:'.$key, 10)->block(3, function () use ($current, $portal, $code, $key) {
                return DB::transaction(function () use ($current, $portal, $code, $key) {
                    $challenge = OtpChallenge::whereKey($current->id)->lockForUpdate()->first();
                    if (! $challenge || $challenge->consumed_at || $challenge->expires_at->lte(now()) || $challenge->attempts >= config('otp.max_attempts') || RateLimiter::tooManyAttempts('otp:fail:'.$key, config('otp.failed_verification_limit'))) {
                        return null;
                    }
                    $challenge->attempts++;
                    $valid = hash_equals($challenge->code_hash, $this->digest($challenge->id, $code));
                    if (! $valid) {
                        RateLimiter::hit('otp:fail:'.$key, 900);
                        if ($challenge->attempts >= config('otp.max_attempts')) {
                            $challenge->consumed_at = now();
                        }
                        $challenge->save();

                        return null;
                    }
                    $challenge->consumed_at = now();
                    $challenge->save();
                    $user = User::where('mobile', $challenge->mobile)->first();
                    if ($user?->suspended_at || ($portal === Portal::Admin && ! $user?->hasStaffAccess())) {
                        return null;
                    }
                    if (! $user) {
                        $user = new User;
                        $user->mobile = $challenge->mobile;
                        $user->name = 'کاربر کیوسک';
                    }
                    $user->mobile_verified_at = now();
                    $user->save();

                    return $user;
                });
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['code' => 'درخواست دیگری در حال پردازش است. چند لحظه دیگر تلاش کنید.']);
        }
        if (! $user) {
            $this->invalid();
        }

        return $user;
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['code' => 'کد معتبر نیست یا مهلت آن تمام شده است. کد را بررسی کنید یا کد جدید بگیرید.']);
    }

    private function key(#[\SensitiveParameter] string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key'));
    }

    private function digest(string $id, #[\SensitiveParameter] string $code): string
    {
        return $this->key($id.'|'.$code);
    }
}
