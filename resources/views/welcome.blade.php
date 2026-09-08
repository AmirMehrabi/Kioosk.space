@extends('layouts.community')
@section('content')
<section class="pb-10">
    <p class="mb-3 font-bold text-pomegranate">پیشنهادهای خوب، نزدیک شما</p>
    <h1 class="text-4xl font-extrabold sm:text-5xl">کجا بریم؟</h1>
    <p class="mt-5 max-w-2xl leading-8 text-secondary">رستوران، کافه، فروشگاه یا هر جای دیگری را در سراسر ایران با کمک تجربه واقعی آدم‌های شهر پیدا کنید.</p>
    <form action="{{ route('home') }}#places" class="panel mt-7 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" role="search">
        <label>نام مکان<input class="field" name="query" type="search" value="{{ request('query') }}" placeholder="مثلاً کافه"></label>
        <x-city-select :cities="$cities" id="city-search" name="city" label="شهر" :value="request('city')" />
        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
        <button class="button-primary self-end">جست‌وجو</button>
    </form>
    <a class="mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-pomegranate" href="{{ route('discovery', request()->only('query', 'city', 'category')) }}"><x-icon name="pin" />کشف مکان‌ها روی نقشه<x-icon name="arrow-left" class="size-4" /></a>
</section>
<nav aria-label="دسته‌بندی‌ها" class="mb-10 flex gap-2 overflow-x-auto border-b border-border pb-3">
    <a @class(['nav-link shrink-0 px-3', 'bg-pomegranate/5 text-pomegranate' => ! request('category')]) href="{{ route('home', request()->only('query', 'city')) }}#places" @unless(request('category')) aria-current="true" @endunless>همه مکان‌ها</a>
    @foreach($categories as $category)
        <a @class(['nav-link shrink-0 px-3', 'bg-pomegranate/5 text-pomegranate' => (string) request('category') === (string) $category->id]) href="{{ route('home', [...request()->only('query', 'city'), 'category' => $category->id]) }}#places" @if((string) request('category') === (string) $category->id) aria-current="true" @endif>{{ $category->name }}</a>
    @endforeach
</nav>
<section class="mb-12" aria-labelledby="recent-reviews-title">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div><h2 id="recent-reviews-title" class="text-2xl font-bold">تازه‌ترین تجربه‌ها</h2><p class="mt-2 text-sm leading-7 text-secondary">آدم‌ها کجا رفته‌اند و چه تجربه‌ای داشته‌اند؟</p></div>
        <a class="nav-link text-pomegranate" href="{{ route('contribute') }}">تجربه تو چه بود؟<x-icon name="arrow-left" class="ms-2 size-4" /></a>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($recentReviews as $review)
            <article class="flex min-w-0 flex-col rounded-2xl border border-border bg-surface p-5">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-pomegranate/10 font-bold text-pomegranate" aria-hidden="true">{{ mb_substr($review->author->name, 0, 1) }}</span>
                    <div class="min-w-0"><p class="break-words font-semibold">{{ $review->author->name }}</p><time class="mt-1 block text-xs text-muted" datetime="{{ $review->created_at->toIso8601String() }}">{{ \App\Support\PersianDate::format($review->created_at->copy()->timezone('Asia/Tehran')->toDateString()) }}</time></div>
                </div>
                <a class="mt-4 text-lg font-bold hover:text-pomegranate" href="{{ route('businesses.show', $review->business->slug) }}">{{ $review->business->name }}</a>
                <p class="mt-1 text-xs text-muted">{{ $review->business->city }}</p>
                <x-review-stars class="mt-3" :rating="$review->rating" />
                <p class="my-4 whitespace-pre-wrap break-words text-sm leading-7 text-secondary">{{ \Illuminate\Support\Str::limit($review->body, 220) }}</p>
                @if($review->photos->isNotEmpty())
                    <div class="mb-4 flex gap-2">@foreach($review->photos as $photo)<a class="min-w-0 flex-1" href="{{ route('reviews.show', $review) }}"><img class="h-24 w-full rounded-lg object-cover" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="عکس تجربه {{ $review->author->name }} در {{ $review->business->name }}" loading="lazy"></a>@endforeach</div>
                @endif
                <a class="mt-auto inline-flex min-h-11 items-center justify-between gap-2 border-t border-border pt-3 text-sm font-semibold text-pomegranate" href="{{ route('reviews.show', $review) }}">خواندن تجربه و گفت‌وگو<x-icon name="arrow-left" class="size-4" /></a>
            </article>
        @empty
            <div class="panel sm:col-span-2 lg:col-span-3"><p class="text-sm leading-7 text-secondary">هنوز تجربه‌ای برای این مکان‌ها منتشر نشده. اولین تجربه را تو بنویس.</p><a class="button-secondary mt-4" href="{{ route('contribute') }}">نوشتن تجربه</a></div>
        @endforelse
    </div>
</section>
<section id="places">
    <h2 class="mb-5 text-2xl font-bold">مکان‌های شهر</h2>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($businesses as $business)
        <article class="overflow-hidden rounded-2xl border border-border bg-surface">
            <a href="{{ route('businesses.show', $business->slug) }}">
                @php($cardPhoto = $business->featuredPhotos->first() ?? $business->photos->first())
                @if($cardPhoto)<img class="h-48 w-full object-cover" src="{{ route('media.show', [$cardPhoto, 'thumbnail' => 1]) }}" alt="{{ $business->name }}" loading="lazy">@else<div class="flex h-36 items-center justify-center bg-soft text-muted">هنوز عکسی ثبت نشده</div>@endif
                <div class="p-5"><h3 class="text-xl font-bold">{{ $business->name }}</h3><p class="mt-3 text-sm text-secondary">{{ $business->city }} · {{ $business->address }}</p><x-review-stars class="mt-4" :rating="$business->reviews_avg_rating ?? 0" /><p class="mt-4 text-pomegranate">★ {{ $business->reviews_count ? number_format($business->reviews_avg_rating, 1) : 'بدون امتیاز' }} <span class="text-sm text-muted">({{ $business->reviews_count }} تجربه)</span></p></div>
            </a>
        </article>
    @empty
        <div class="panel sm:col-span-2"><h3 class="text-xl font-bold">هنوز مکانی پیدا نشد</h3><p class="mt-3 leading-8 text-secondary">شما اولین مکان این جست‌وجو را معرفی کنید.</p><a class="button-primary mt-5" href="{{ route('contribute') }}">افزودن مکان یا نوشتن نظر</a></div>
    @endforelse
    </div>
    <div class="mt-6">{{ $businesses->links() }}</div>
</section>
@endsection
