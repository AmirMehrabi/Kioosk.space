<?php

namespace App\Providers;

use App\Models\City;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.navbar', function (\Illuminate\View\View $view): void {
            $view->with('searchCities', City::where('is_active', true)->orderBy('position')->orderBy('name')->get());
        });

        RateLimiter::for('otp-send', function (Request $request) {
            $portal = (string) $request->route('portal', 'public');

            return Limit::perMinute((int) config('otp.send_per_minute'))->by($portal.'|'.$request->ip())->response(function (Request $request, array $headers) {
                return response()->view('errors.429', [], 429, $headers);
            });
        });
        RateLimiter::for('otp-verify', function (Request $request) {
            $portal = (string) $request->route('portal', 'public');

            return Limit::perMinute((int) config('otp.verify_per_minute'))->by($portal.'|'.$request->ip())->response(function (Request $request, array $headers) {
                return response()->view('errors.429', [], 429, $headers);
            });
        });
    }
}
