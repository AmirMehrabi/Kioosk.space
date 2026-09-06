@props(['user'])
@php($initial = mb_substr(trim($user->name) ?: 'ک', 0, 1))

<details class="group relative" data-profile-menu>
    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-xl px-1 text-sm font-semibold hover:text-pomegranate focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-pomegranate [&::-webkit-details-marker]:hidden">
        <span class="flex size-10 items-center justify-center rounded-full bg-pomegranate/10 text-base font-extrabold text-pomegranate" aria-hidden="true">{{ $initial }}</span>
        <span class="hidden max-w-28 truncate sm:block">{{ $user->name }}</span>
        <svg class="size-4 text-muted transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 7 5 5 5-5"/></svg>
        <span class="sr-only">باز کردن منوی حساب کاربری</span>
    </summary>
    <div class="absolute left-0 z-30 mt-2 w-64 rounded-2xl border border-border bg-surface p-2 shadow-soft">
        <div class="flex items-center gap-3 border-b border-border px-3 py-3">
            <span class="flex size-10 items-center justify-center rounded-full bg-pomegranate/10 font-extrabold text-pomegranate" aria-hidden="true">{{ $initial }}</span>
            <div class="min-w-0"><p class="truncate font-bold">{{ $user->name }}</p><p class="mt-1 text-xs text-muted">حساب کاربری کیوسک</p></div>
        </div>
        <nav class="my-2 space-y-1" aria-label="حساب و پرتال‌ها">
            @foreach(['account' => $user->hasStaffAccess() ? 'پرتال کاربر' : 'حساب کاربری', 'contributions.index' => 'مشارکت‌های من'] as $destination => $label)
                <a href="{{ route($destination) }}" @class(['admin-nav-item', 'admin-nav-item-active' => request()->routeIs($destination)]) @if(request()->routeIs($destination)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            @if($user->ownedBusinesses()->exists())
                <a href="{{ route('business.dashboard') }}" @class(['admin-nav-item', 'admin-nav-item-active' => request()->routeIs('business.dashboard')]) @if(request()->routeIs('business.dashboard')) aria-current="page" @endif>پرتال کسب‌وکار</a>
            @endif
            @if($user->hasStaffAccess())
                <a href="{{ route('admin.dashboard') }}" @class(['admin-nav-item', 'admin-nav-item-active' => request()->routeIs('admin.*')]) @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>پرتال مدیریت</a>
            @endif
        </nav>
        <form action="{{ route('logout') }}" method="POST" class="border-t border-border pt-2">
            @csrf
            <button type="submit" class="flex min-h-11 w-full items-center rounded-xl px-3 text-right text-sm font-semibold text-pomegranate hover:bg-pomegranate/5">خروج از حساب</button>
        </form>
    </div>
</details>
