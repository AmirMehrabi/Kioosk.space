<?php

use App\Enums\Portal;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReviewController;
use App\Http\Middleware\AuthResponseHeaders;
use App\Http\Middleware\EnsureContributor;
use App\Http\Middleware\EnsurePortalAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', [BusinessController::class, 'index'])->name('home');
Route::get('/contribute', [ContributionController::class, 'create'])->middleware(AuthResponseHeaders::class)->name('contribute');
Route::get('/businesses/search', [BusinessController::class, 'search'])->middleware('throttle:60,1,business-search')->name('businesses.search');
Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');

Route::middleware([AuthResponseHeaders::class, EnsureContributor::class, 'throttle:120,1,contributions'])->group(function () {
    $contributions = ContributionController::class;
    $reviews = ReviewController::class;
    $media = MediaController::class;
    Route::get('/account/contributions', [$contributions, 'index'])->name('contributions.index');
    Route::post('/contribution-drafts', [$contributions, 'store']);
    Route::get('/contribution-drafts/{draft}', [$contributions, 'show']);
    Route::put('/contribution-drafts/{draft}', [$contributions, 'update']);
    Route::delete('/contribution-drafts/{draft}', [$contributions, 'destroy'])->name('drafts.destroy');
    Route::post('/contribution-drafts/{draft}/submit', [$contributions, 'submit'])->middleware('throttle:12,1,contribution-submit');
    Route::post('/contribution-drafts/{draft}/photos', [$media, 'store'])->middleware('throttle:20,1,contribution-photos');
    Route::delete('/contribution-drafts/{draft}/photos/{media}', [$media, 'destroy']);
    Route::delete('/media/{media}', [$media, 'removePublished'])->name('media.destroy');
    Route::get('/reviews/{review}/edit', [$reviews, 'edit'])->name('reviews.edit');
    Route::delete('/reviews/{review}', [$reviews, 'destroy'])->name('reviews.destroy');
    Route::match(['post', 'delete'], '/reviews/{review}/helpful', [$reviews, 'helpful'])->name('reviews.helpful');
    Route::post('/reviews/{review}/comments', [$reviews, 'comment'])->name('reviews.comments');
    Route::match(['put', 'delete'], '/comments/{comment}', [$reviews, 'updateComment'])->name('comments.update');
    Route::match(['put', 'delete'], '/reviews/{review}/owner-reply', [$reviews, 'ownerReply'])->name('reviews.owner-reply');
    Route::match(['post', 'delete'], '/businesses/{business}/save', [$reviews, 'save'])->name('businesses.save');
    Route::post('/reports', [$reviews, 'report'])->name('reports.store');
});
Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
Route::middleware([AuthResponseHeaders::class, EnsurePortalAccess::class.':admin', 'throttle:60,1,moderation'])->prefix('admin')->group(function () {
    $moderation = ModerationController::class;
    Route::get('/submissions', [$moderation, 'index'])->name('admin.submissions');
    Route::get('/submissions/{business}', [$moderation, 'show'])->name('admin.submissions.show');
    Route::post('/submissions/{business}', [$moderation, 'update'])->name('admin.submissions.update');
    Route::get('/reports', [$moderation, 'reports'])->name('admin.reports');
    Route::post('/reports/{report}', [$moderation, 'report'])->name('admin.reports.update');
});
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
    Route::get('/admin/dashboard', AdminDashboardController::class)->middleware(EnsurePortalAccess::class.':admin')->name('admin.dashboard');
});
