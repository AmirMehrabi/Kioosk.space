@props(['minimal' => false, 'portal' => null])

<header class="border-b border-border/70 bg-surface">
    <nav class="mx-auto flex min-h-20 max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:gap-x-3" aria-label="فهرست اصلی">
        <img src="{{ asset('images/logo/logo-fa.png') }}" class="w-42" alt="">
        <!-- <a href="{{ route('home') }}" aria-label="صفحه اصلی کیوسک" class="shrink-0 rounded-lg text-3xl font-extrabold tracking-tight">کیوسک<span class="text-pomegranate">.</span></a> -->
        <a href="{{ route('home') }}" @class(['nav-link hidden sm:inline-flex', 'nav-link-active' => request()->routeIs('home', 'businesses.show', 'reviews.show')]) @if(request()->routeIs('home')) aria-current="page" @endif>کشف مکان‌ها</a>
        <div class="ms-auto flex items-center gap-2 sm:gap-4">
            @unless($minimal)
                <a href="{{ route('contribute') }}" class="button-primary !px-3 !py-2 sm:!px-4" @if(request()->routeIs('contribute')) aria-current="page" @endif><span aria-hidden="true" class="text-lg font-normal">+</span><span class="sm:hidden">ثبت تجربه</span><span class="hidden sm:inline">افزودن مکان یا نوشتن نظر</span></a>
            @endunless
            @auth
                <x-profile-menu :user="auth()->user()" />
            @else
                <a class="nav-link whitespace-nowrap" href="{{ route(isset($portal) ? $portal->route('login') : 'login') }}">ورود@if($portal !== \App\Enums\Portal::Admin)<span class="hidden sm:inline"> / ثبت‌نام</span>@endif</a>
            @endauth
        </div>
    </nav>
</header>
