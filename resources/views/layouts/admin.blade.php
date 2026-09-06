@extends('layouts.community')

@section('content')
    <div class="grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-5 lg:h-fit" aria-label="ناوبری مدیریت">
            <div class="rounded-2xl border border-border bg-surface p-3 shadow-soft">
                <div class="border-b border-border px-3 pb-4 pt-2">
                    <p class="text-xs font-semibold text-muted">پرتال مدیریت</p>
                    <p class="mt-1 font-bold">{{ auth()->user()->name }}</p>
                </div>
                <nav class="mt-3 flex gap-2 overflow-x-auto lg:block lg:space-y-1" aria-label="بخش‌های مدیریت">
                    <a @class(['admin-nav-item', 'admin-nav-item-active' => request()->routeIs('admin.dashboard')]) href="{{ route('admin.dashboard') }}">داشبورد</a>
                    <a @class(['admin-nav-item', 'admin-nav-item-active' => request()->routeIs('admin.submissions*')]) href="{{ route('admin.submissions') }}">مشارکت‌ها</a>
                    <a @class(['admin-nav-item', 'admin-nav-item-active' => request()->routeIs('admin.reports*')]) href="{{ route('admin.reports') }}">گزارش‌ها</a>
                    <a class="admin-nav-item" href="{{ route('home') }}">مشاهده کیوسک</a>
                </nav>
            </div>
        </aside>

        <div>@yield('admin-content')</div>
    </div>
@endsection
