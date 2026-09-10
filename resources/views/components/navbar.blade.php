@props(['minimal' => false, 'portal' => null])
<header @class(['mobile-top-nav relative z-30 bg-surface', 'border-b border-border/70' => ! request()->routeIs('home')])>
    <nav class="flex min-h-22 flex-wrap items-center gap-x-5 gap-y-4 px-4 py-4" aria-label="فهرست اصلی">
        <a href="{{ route('home') }}" aria-label="صفحه اصلی کیوسک" class="shrink-0"><img src="{{ asset('images/logo/logo-fa.png') }}" class="w-32 sm:w-36" alt=""></a>
        @unless($minimal)
            <form action="{{ route('discovery') }}" role="search" aria-label="جست‌وجوی مکان‌ها" class="navbar-search order-last flex w-full items-center rounded-xl border border-border bg-surface p-1.5 shadow-soft lg:order-none lg:mx-auto lg:w-auto lg:min-w-0 lg:max-w-xl lg:flex-1">
                <label class="sr-only" for="navbar-query">نام مکان یا دسته‌بندی</label>
                <input id="navbar-query" name="query" type="search" maxlength="180" value="{{ request('query') }}" placeholder="کافه، رستوران، یک جای خوب…" class="min-h-11 min-w-0 flex-1 bg-transparent px-3 text-sm outline-none">
                <div class="w-28 shrink-0 border-s border-border ps-2 sm:w-36"><x-city-select :cities="$searchCities" id="navbar-city" name="city" label="شهر" :value="request('city', session('discovery.city', ''))" placeholder="کدام شهر؟" /></div>
                <button type="submit" class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-pomegranate text-white transition-colors hover:bg-pomegranate-dark" aria-label="جست‌وجو"><x-icon name="search" /></button>
            </form>
        @endunless
        <div class="ms-auto flex shrink-0 items-center gap-3 sm:gap-5">
            @unless($minimal)<a href="{{ route('contribute') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-secondary hover:text-pomegranate" @if(request()->routeIs('contribute')) aria-current="page" @endif><x-icon name="edit" class="size-4" /><span>نوشتن تجربه</span></a>@endunless
            @auth
                <x-profile-menu :user="auth()->user()" />
            @else
                <a class="button-secondary whitespace-nowrap !px-3 sm:!px-4" href="{{ route(isset($portal) ? $portal->route('login') : 'login') }}">ورود@if($portal !== \App\Enums\Portal::Admin)<span class="hidden sm:inline"> / ثبت‌نام</span>@endif</a>
            @endauth
        </div>
    </nav>
</header>

<nav data-mobile-nav class="mobile-bottom-nav" aria-label="دسترسی سریع">
    <div class="mobile-bottom-nav-inner">
        <a href="{{ route('home') }}" aria-label="خانه" title="خانه" @class(['mobile-nav-item', 'mobile-nav-item-active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif>
            <x-icon name="home" />
        </a>
        <a href="{{ route('discovery') }}" aria-label="کشف مکان‌ها" title="کشف مکان‌ها" @class(['mobile-nav-item', 'mobile-nav-item-active' => request()->routeIs('discovery')]) @if(request()->routeIs('discovery')) aria-current="page" @endif>
            <x-icon name="map" />
        </a>
        <a href="{{ route('contribute') }}" aria-label="افزودن مکان یا تجربه" title="افزودن مکان یا تجربه" class="mobile-nav-item mobile-nav-create" @if(request()->routeIs('contribute')) aria-current="page" @endif>
            <span class="mobile-nav-create-icon"><x-icon name="plus" /></span>
        </a>
        @auth
            <a href="{{ route('contributions.index') }}" aria-label="مشارکت‌های من" title="مشارکت‌های من" @class(['mobile-nav-item', 'mobile-nav-item-active' => request()->routeIs('contributions.*')]) @if(request()->routeIs('contributions.*')) aria-current="page" @endif>
                <x-icon name="edit" />
            </a>
            <a href="{{ route('account') }}" aria-label="حساب کاربری" title="حساب کاربری" @class(['mobile-nav-item', 'mobile-nav-item-active' => request()->routeIs('account')]) @if(request()->routeIs('account')) aria-current="page" @endif>
                <x-icon name="user" />
            </a>
        @else
            <a href="{{ route('login') }}" aria-label="ورود" title="ورود" @class(['mobile-nav-item', 'mobile-nav-item-active' => request()->routeIs('login')]) @if(request()->routeIs('login')) aria-current="page" @endif>
                <x-icon name="phone" />
            </a>
            <a href="{{ route('contact') }}" aria-label="تماس با ما" title="تماس با ما" @class(['mobile-nav-item', 'mobile-nav-item-active' => request()->routeIs('contact')]) @if(request()->routeIs('contact')) aria-current="page" @endif>
                <x-icon name="message" />
            </a>
        @endauth
    </div>
</nav>
