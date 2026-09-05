<?php

use App\Enums\Portal;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\PortalController;
use App\Http\Middleware\AuthResponseHeaders;
use App\Http\Middleware\EnsurePortalAccess;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/businesses/{slug}', [BusinessController::class, 'show'])->name('businesses.show');

Route::middleware(AuthResponseHeaders::class)->group(function () {
    foreach (Portal::cases() as $portal) {
        $prefix = $portal === Portal::Public ? '' : $portal->value;
        Route::prefix($prefix)->group(function () use ($portal) {
            Route::get('/login', [OtpController::class, 'create'])->defaults('portal', $portal->value)->name($portal->route('login'));
            if ($portal !== Portal::Admin) {
                Route::get('/register', [OtpController::class, 'create'])->defaults('portal', $portal->value)->name($portal->route('register'));
            }
            Route::post('/login', [OtpController::class, 'store'])->defaults('portal', $portal->value)->middleware('throttle:otp-send')->block(10, 5)->name($portal->route('otp.send'));
            Route::get('/verify', [OtpController::class, 'challenge'])->defaults('portal', $portal->value)->name($portal->route('verify'));
            Route::post('/verify', [OtpController::class, 'verify'])->defaults('portal', $portal->value)->middleware('throttle:otp-verify')->block(10, 5)->name($portal->route('otp.verify'));
            Route::post('/resend', [OtpController::class, 'resend'])->defaults('portal', $portal->value)->middleware('throttle:otp-send')->block(10, 5)->name($portal->route('otp.resend'));
        });
    }
    Route::post('/logout', [OtpController::class, 'destroy'])->block(10, 5)->name('logout');
    Route::get('/account', PortalController::class)->defaults('portal', 'public')->middleware(EnsurePortalAccess::class.':public')->name('account');
    Route::get('/business/dashboard', PortalController::class)->defaults('portal', 'business')->middleware(EnsurePortalAccess::class.':business')->name('business.dashboard');
    Route::get('/admin/dashboard', PortalController::class)->defaults('portal', 'admin')->middleware(EnsurePortalAccess::class.':admin')->name('admin.dashboard');
});
