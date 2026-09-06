<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContributor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $request->expectsJson() ? response()->json(['message' => 'برای ثبت نهایی وارد شوید.'], 401) : redirect()->route('login', ['contribute' => 1]);
        }
        abort_if($request->user()->suspended_at || ! $request->user()->mobile_verified_at, 403, 'حساب شما اجازه مشارکت ندارد.');

        return $next($request);
    }
}
