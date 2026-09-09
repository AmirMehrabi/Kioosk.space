@extends('layouts.community')
@section('title', 'جای خوب، با تجربه آدم‌ها')
@section('hero')
<section class="home-hero relative isolate overflow-hidden bg-ink text-white" aria-labelledby="hero-title">
    <img class="absolute inset-0 -z-20 h-full w-full object-cover" src="{{ $featuredBusiness ? route('media.show', $featuredBusiness->heroPhoto) : asset('images/businesses/cafe-counter.jpg') }}" alt="{{ $featuredBusiness ? 'فضای '.$featuredBusiness->name : '' }}" fetchpriority="high" decoding="async">
    <div class="home-hero-shade absolute inset-0 -z-10" aria-hidden="true"></div>
    <div class="relative mx-auto flex min-h-140 max-w-6xl flex-col justify-between gap-12 px-5 py-10 sm:min-h-148 sm:px-6 sm:py-12 lg:min-h-152 lg:px-4">
        <div class="flex max-w-lg flex-col items-start gap-6">
            <p class="inline-flex items-center gap-2.5 text-xs font-semibold text-white/90"><span class="h-px w-7 bg-white/70" aria-hidden="true"></span>کیوسک · شهر از نگاه شما</p>
            <h1 id="hero-title" class="text-[2.5rem] font-extrabold leading-[1.45] tracking-tight sm:text-5xl lg:text-[3.5rem]">جای خوب،<br>همین دوروبره.</h1>
            <p class="max-w-sm text-sm leading-8 text-white/90 sm:text-base">کافهٔ دنج، غذای خوش‌طعم، یک کشف تازه.<br>با تجربهٔ واقعی آدم‌ها، جای بعدی‌ات را پیدا کن.</p>
            <a class="button-primary min-w-44 gap-5 !rounded-lg !py-3.5" href="{{ route('discovery') }}">بریم کشف کنیم<x-icon name="arrow-left" class="size-4" /></a>
        </div>
        <div class="flex flex-wrap items-end justify-between gap-6 border-t border-white/25 pt-6">
            @if($featuredBusiness)
                <div class="flex max-w-xl flex-col items-start gap-2">
                    <p class="inline-flex items-center gap-2 text-xs text-white/80"><x-icon name="pin" class="size-3.5" />{{ $featuredBusiness->city }}<span aria-hidden="true">·</span>پیشنهاد کیوسک</p>
                    <a class="group inline-flex min-h-11 items-center gap-4 text-xl font-bold sm:text-2xl" href="{{ route('businesses.show', $featuredBusiness->slug) }}">{{ $featuredBusiness->name }}<x-icon name="arrow-left" class="size-5 transition-transform group-hover:-translate-x-1 motion-reduce:transform-none" /></a>
                    <p class="max-w-lg text-sm leading-7 text-white/85">{{ \Illuminate\Support\Str::limit($featuredBusiness->description ?: 'این‌جا را بشناس؛ عکس‌ها و تجربه‌های دیگران را ببین و برای سر زدن تصمیم بگیر.', 140) }}</p>
                </div>
            @else
                <p class="flex items-center gap-3 text-sm leading-7 text-white/85"><x-icon name="compass" class="size-5" />یک جای تازه، یک تجربهٔ تازه.</p>
            @endif
            <a class="inline-flex min-h-11 shrink-0 items-center gap-3 text-sm font-semibold text-white/90 hover:text-white" href="#recent-reviews">از نگاه آدم‌های شهر<x-icon name="chevron-down" class="size-4" /></a>
        </div>
    </div>
</section>
@endsection
@section('content')
<section id="recent-reviews" class="scroll-mt-6 py-4" aria-labelledby="recent-reviews-title" data-review-feed>
    <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
        <div><p class="mb-2 text-xs font-semibold text-muted">تجربه‌های کوچک، انتخاب‌های بهتر</p><h2 id="recent-reviews-title" class="text-2xl font-extrabold sm:text-3xl">تازه‌ترین تجربه‌ها</h2></div>
        <span class="inline-flex items-center gap-2 text-xs text-muted"><span class="size-1.5 rounded-full bg-positive"></span>از آدم‌های همین شهر</span>
    </div>
    <div id="review-feed" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-busy="false">
        @include('home-review-cards')
    </div>
    @if($recentReviews->isEmpty())<div class="rounded-2xl border border-dashed border-border px-6 py-12 text-center"><x-icon name="message" class="mx-auto mb-4 size-8 text-pomegranate" /><h3 class="font-bold">اولین تجربه، شروع یک گفت‌وگوست.</h3><p class="mt-3 text-sm text-muted">جای خوبی می‌شناسی؟ تجربه‌ات را با بقیه به اشتراک بگذار.</p><a href="{{ route('contribute') }}" class="button-primary mt-6">نوشتن اولین تجربه</a></div>@endif
    <div class="mt-9 flex flex-col items-center gap-3">
        @if($recentReviews->hasMorePages())<a href="{{ $recentReviews->nextPageUrl() }}#recent-reviews" data-load-reviews class="button-secondary min-w-52 !rounded-full !px-7 !py-3 font-semibold" aria-controls="review-feed"><span data-load-label>تجربه‌های بیشتر</span><x-icon name="chevron-down" class="size-4" /></a>@endif
        <p data-feed-status role="status" aria-live="polite" class="text-center text-sm text-muted"></p>
    </div>
</section>

<section class="mt-10 border-t border-border pt-10 pb-4 sm:mt-14 sm:pt-12" aria-labelledby="categories-title">
    <div class="mb-7 text-center"><p class="mb-2 text-xs font-semibold text-muted">برای هر حال‌وهوا</p><h2 id="categories-title" class="text-2xl font-extrabold">امروز دنبال چی می‌گردی؟</h2></div>
    @php($categoryIcons = ['رستوران' => 'utensils', 'کافه' => 'coffee', 'خرید' => 'shopping-bag', 'پزشک' => 'medical', 'زیبایی' => 'sparkles', 'خدمات منزل' => 'home', 'گردشگری' => 'compass', 'سایر' => 'grid'])
    <nav aria-label="دسته‌بندی‌ها" class="grid grid-cols-2 gap-6 sm:grid-cols-4">
        @foreach($categories as $category)
            <a href="{{ route('discovery', [...request()->only('city'), 'category' => $category->id]) }}" class="home-category group flex min-h-32 flex-col items-center justify-center gap-4 rounded border border-border bg-surface px-10 py-7  font-semibold text-secondary transition hover:-translate-y-1 hover:border-pomegranate/30 hover:text-pomegranate motion-reduce:transform-none">
                <x-icon :name="$categoryIcons[$category->name] ?? 'grid'" class="size-11 stroke-[1.4] transition-transform group-hover:scale-110 motion-reduce:transform-none" /><span>{{ $category->name }}</span>
            </a>
        @endforeach
    </nav>
</section>
@endsection
