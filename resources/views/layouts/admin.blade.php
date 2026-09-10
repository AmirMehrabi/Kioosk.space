@extends('layouts.community')

@section('breadcrumbs')
    @php
        $breadcrumbs = [['label' => 'پرتال مدیریت', 'url' => route('admin.dashboard')]];
        if (request()->routeIs('admin.submissions*')) {
            $breadcrumbs[] = ['label' => 'مشارکت‌ها', 'url' => route('admin.submissions')];
        } elseif (request()->routeIs('admin.reports*')) {
            $breadcrumbs[] = ['label' => 'گزارش‌های محتوا'];
        } elseif (request()->routeIs('admin.businesses*')) {
            $breadcrumbs[] = ['label' => 'کسب‌وکارها', 'url' => route('admin.businesses.index')];
        } elseif (request()->routeIs('admin.claims*')) {
            $breadcrumbs[] = ['label' => 'درخواست‌های مالکیت'];
        } elseif (request()->routeIs('admin.users*')) {
            $breadcrumbs[] = ['label' => 'کاربران و همکاران'];
        } elseif (request()->routeIs('admin.cities*')) {
            $breadcrumbs[] = ['label' => 'شهرها', 'url' => route('admin.cities.index')];
        } elseif (request()->routeIs('admin.categories*')) {
            $breadcrumbs[] = ['label' => 'دسته‌بندی‌ها', 'url' => route('admin.categories.index')];
        } elseif (request()->routeIs('admin.audit-log*')) {
            $breadcrumbs[] = ['label' => 'تاریخچه فعالیت‌ها'];
        }
        if (request()->routeIs('admin.submissions.show')) {
            $breadcrumbs[] = ['label' => $business->name];
        }
    @endphp
    <x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('content')
    <div class="grid gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-5 lg:h-fit" aria-label="ناوبری مدیریت">
            <div class="rounded-2xl border border-border/70 bg-surface p-3">
                <div class="border-b border-border px-3 pb-4 pt-2">
                    <p class="text-xs font-semibold text-muted">پرتال مدیریت</p>
                </div>
                <nav class="mt-3 flex gap-2 overflow-x-auto lg:block lg:space-y-1" aria-label="بخش‌های مدیریت">
                    @foreach(['admin.dashboard' => 'داشبورد', 'admin.businesses.index' => 'کسب‌وکارها', 'admin.submissions' => 'مشارکت‌ها', 'admin.claims.index' => 'درخواست‌های مالکیت', 'admin.reports' => 'گزارش‌ها'] as $destination => $label)
                        <a @class(['admin-nav-item shrink-0', 'admin-nav-item-active' => request()->routeIs($destination, $destination.'.*')]) href="{{ route($destination) }}" @if(request()->routeIs($destination, $destination.'.*')) aria-current="{{ request()->routeIs($destination) ? 'page' : 'location' }}" @endif>{{ $label }}</a>
                    @endforeach
                    @if(auth()->user()->platform_role === \App\Enums\PlatformRole::Superadmin)
                        <div class="my-3 hidden border-t border-border lg:block"></div>
                        @foreach(['admin.users.index' => 'کاربران و همکاران', 'admin.cities.index' => 'شهرها', 'admin.categories.index' => 'دسته‌بندی‌ها', 'admin.audit-log.index' => 'تاریخچه فعالیت‌ها'] as $destination => $label)
                            <a @class(['admin-nav-item shrink-0', 'admin-nav-item-active' => request()->routeIs($destination, str_replace('.index', '.*', $destination))]) href="{{ route($destination) }}" @if(request()->routeIs($destination, str_replace('.index', '.*', $destination))) aria-current="page" @endif>{{ $label }}</a>
                        @endforeach
                    @endif
                </nav>
                <a class="nav-link mt-3 hidden w-full gap-2 border-t border-border pt-3 lg:flex" href="{{ route('home') }}">مشاهده کیوسک <span aria-hidden="true">←</span></a>
            </div>
        </aside>

        <div class="min-w-0">@yield('admin-content')</div>
    </div>
@endsection
