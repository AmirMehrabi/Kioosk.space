@extends('layouts.community')
@section('title', 'جای خوب، با تجربه آدم‌ها')
@section('content')
<section class="home-hero relative isolate overflow-hidden rounded-[1.75rem]" aria-labelledby="hero-title">
    <div class="relative z-10 max-w-xl px-6 py-10 sm:px-10 sm:py-14 lg:py-16">
        <p class="mb-5 inline-flex items-center gap-2 text-xs font-bold tracking-wide text-pomegranate"><span class="size-1.5 rounded-full bg-pomegranate"></span>کیوسک · شهر از نگاه شما</p>
        <h1 id="hero-title" class="text-4xl font-extrabold leading-[1.5] tracking-tight sm:text-5xl">هر گوشهٔ شهر،<br><span class="text-pomegranate">یک تجربهٔ خوب.</span></h1>
        <p class="mt-5 max-w-sm text-sm leading-8 text-secondary sm:text-base">از قهوهٔ سرِ کوچه تا یک شام به‌یادماندنی.<br class="hidden sm:block"> جای بعدی‌ات را با تجربهٔ واقعی آدم‌ها پیدا کن.</p>
        <div class="mt-7 flex flex-wrap gap-3"><a class="button-primary shadow-soft" href="{{ route('discovery') }}">بریم کشف کنیم<x-icon name="arrow-left" class="size-4" /></a><a class="inline-flex min-h-11 items-center gap-2 px-3 text-sm font-semibold text-secondary hover:text-pomegranate" href="#recent-reviews">تازه‌های شهر<x-icon name="chevron-down" class="size-4" /></a></div>
    </div>
    <x-hero-city />
</section>

<section id="recent-reviews" class="scroll-mt-6 pt-12 sm:pt-16" aria-labelledby="recent-reviews-title" data-review-feed>
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
    <nav aria-label="دسته‌بندی‌ها" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
        @foreach($categories as $category)
            <a href="{{ route('discovery', [...request()->only('city'), 'category' => $category->id]) }}" class="home-category group flex min-h-32 flex-col items-center justify-center gap-4 rounded-2xl border border-border bg-surface px-3 py-5 text-sm font-semibold text-secondary transition hover:-translate-y-1 hover:border-pomegranate/30 hover:text-pomegranate motion-reduce:transform-none">
                <x-icon :name="$categoryIcons[$category->name] ?? 'grid'" class="size-7 stroke-[1.4] transition-transform group-hover:scale-110 motion-reduce:transform-none" /><span>{{ $category->name }}</span>
            </a>
        @endforeach
    </nav>
</section>
@endsection
