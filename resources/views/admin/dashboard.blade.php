@extends('layouts.community')
@section('title', 'داشبورد مدیریت')
@section('content')
    <div class="grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-5 lg:h-fit" aria-label="ناوبری مدیریت">
            <div class="rounded-2xl border border-border bg-surface p-3 shadow-soft">
                <div class="border-b border-border px-3 pb-4 pt-2">
                    <p class="text-xs font-semibold text-muted">پرتال مدیریت</p>
                    <p class="mt-1 font-bold">{{ auth()->user()->name }}</p>
                </div>
                <nav class="mt-3 flex gap-2 overflow-x-auto lg:block lg:space-y-1" aria-label="بخش‌های مدیریت">
                    <a class="admin-nav-item admin-nav-item-active shrink-0" href="{{ route('admin.dashboard') }}">داشبورد</a>
                    <a class="admin-nav-item shrink-0" href="{{ route('admin.submissions') }}">مشارکت‌ها <span class="admin-nav-count">{{ $pendingSubmissions }}</span></a>
                    <a class="admin-nav-item shrink-0" href="{{ route('admin.reports') }}">گزارش‌ها <span class="admin-nav-count">{{ $openReports }}</span></a>
                    <a class="admin-nav-item shrink-0" href="{{ route('home') }}">مشاهده کیوسک</a>
                </nav>
            </div>
        </aside>

        <div>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-pomegranate">پرتال مدیریت</p>
                    <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">داشبورد</h1>
                    <p class="mt-3 text-secondary">کارهای مهم امروز را از همین‌جا دنبال کنید.</p>
                </div>
                <a class="button-primary" href="{{ route('admin.submissions') }}">بررسی مشارکت‌ها</a>
            </div>

            <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="نمای کلی مدیریت">
                <a class="admin-stat-card" href="{{ route('admin.submissions') }}"><span>در انتظار بررسی</span><strong>{{ $pendingSubmissions }}</strong><small>مکان‌های پیشنهادی</small></a>
                <a class="admin-stat-card" href="{{ route('admin.reports') }}"><span>گزارش‌های باز</span><strong>{{ $openReports }}</strong><small>نیازمند تصمیم</small></a>
                <div class="admin-stat-card"><span>مکان‌های منتشرشده</span><strong>{{ $publishedBusinesses }}</strong><small>در کیوسک</small></div>
                <div class="admin-stat-card"><span>تجربه‌های منتشرشده</span><strong>{{ $publishedReviews }}</strong><small>از کاربران</small></div>
            </section>

            <div class="mt-8 grid gap-6 xl:grid-cols-2">
                <section class="panel">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold">مشارکت‌های تازه</h2><a class="text-sm font-semibold text-pomegranate" href="{{ route('admin.submissions') }}">همه مشارکت‌ها</a></div>
                    <div class="mt-4 divide-y divide-border">
                        @forelse($recentSubmissions as $business)
                            <a href="{{ route('admin.submissions.show', $business) }}" class="block py-4 transition hover:text-pomegranate"><div class="flex items-start justify-between gap-3"><div><h3 class="font-bold">{{ $business->name }}</h3><p class="mt-1 text-sm text-muted">{{ $business->city }} · {{ $business->address }}</p></div><span class="admin-status">{{ ['pending' => 'جدید', 'corrections' => 'اصلاح', 'incomplete' => 'ناقص', 'rejected' => 'ردشده'][$business->status] ?? $business->status }}</span></div></a>
                        @empty
                            <p class="py-8 text-center text-sm text-muted">مشارکت تازه‌ای برای بررسی ندارید.</p>
                        @endforelse
                    </div>
                </section>

                <section class="panel">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold">گزارش‌های تازه</h2><a class="text-sm font-semibold text-pomegranate" href="{{ route('admin.reports') }}">همه گزارش‌ها</a></div>
                    <div class="mt-4 divide-y divide-border">
                        @forelse($recentReports as $report)
                            <a href="{{ route('admin.reports') }}" class="block py-4 transition hover:text-pomegranate"><div class="flex items-start justify-between gap-3"><div><h3 class="font-bold">{{ ['review' => 'تجربه', 'comment' => 'دیدگاه', 'owner_reply' => 'پاسخ مالک', 'media' => 'عکس'][$report->content_type] ?? 'محتوا' }}</h3><p class="mt-1 line-clamp-2 text-sm leading-6 text-muted">{{ $report->reason }}</p></div><span class="admin-status">باز</span></div></a>
                        @empty
                            <p class="py-8 text-center text-sm text-muted">گزارش بازی برای رسیدگی ندارید.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
