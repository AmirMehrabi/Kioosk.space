<?php

namespace App\Http\Middleware;

use App\Enums\Portal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAccess
{
    public function handle(Request $request, Closure $next, string $area = 'public'): Response
    {
        $portal = Portal::from($area);
        $user = $request->user();
        if (! $user) {
            return redirect()->route($portal->route('login'));
        }
        if ($user->suspended_at || ! $user->mobile_verified_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($portal->route('login'))->withErrors(['mobile' => 'برای ادامه، دوباره وارد حساب خود شوید.']);
        }
        if ($portal === Portal::Admin) {
            abort_unless($user->hasStaffAccess(), 403);
        }

        return $next($request);
    }
}
