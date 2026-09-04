<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Portal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    private function portal(Request $request): Portal
    {
        return Portal::from($request->route('portal'));
    }

    public function create(Request $request): View
    {
        return view('auth.login', ['portal' => $this->portal($request)]);
    }

    public function store(SendOtpRequest $request): RedirectResponse
    {
        $portal = $this->portal($request);
        $this->otp->send($request, $portal, $request->validated('mobile'));

        return redirect()->route($portal->route('verify'))->with('status', 'در صورت مجاز بودن ورود، کد برای این شماره ارسال می‌شود.');
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $portal = $this->portal($request);
        $challenge = $this->otp->current($request, $portal);
        if (! $challenge) {
            return redirect()->route($portal->route('login'))->with('status', 'برای دریافت کد جدید، شماره موبایل خود را وارد کنید.');
        }

        return view('auth.verify', ['portal' => $portal, 'challenge' => $challenge]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $portal = $this->portal($request);
        $challenge = $this->otp->current($request, $portal);
        if (! $challenge) {
            return redirect()->route($portal->route('login'));
        }
        $this->otp->send($request, $portal, $challenge->mobile);

        return redirect()->route($portal->route('verify'))->with('status', 'در صورت مجاز بودن ورود، کد جدید ارسال می‌شود. فقط آخرین کد معتبر است.');
    }

    public function verify(VerifyOtpRequest $request): RedirectResponse
    {
        $portal = $this->portal($request);
        $user = $this->otp->verify($request, $portal, $request->validated('code'));
        $request->session()->invalidate();
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        if ($portal === Portal::Admin) {
            $request->session()->put('staff_auth', ['user_id' => $user->id, 'verified_at' => now()->timestamp]);
        }

        return redirect()->route($portal->destination());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
