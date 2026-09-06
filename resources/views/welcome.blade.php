@extends('layouts.community')
@section('content')
<section class="pb-10">
    <p class="mb-3 font-bold text-pomegranate">پیشنهادهای خوب، نزدیک شما</p>
    <h1 class="text-4xl font-extrabold sm:text-5xl">کجا بریم؟</h1>
    <p class="mt-5 max-w-2xl leading-8 text-secondary">رستوران، کافه، فروشگاه یا هر جای دیگری را در سراسر ایران با کمک تجربه واقعی آدم‌های شهر پیدا کنید.</p>
    <form action="{{ route('home') }}#places" class="panel mt-7 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" role="search">
        <label>نام مکان<input class="field" name="query" type="search" value="{{ request('query') }}" placeholder="مثلاً کافه"></label>
        <x-city-select :cities="$cities" id="city-search" name="city" label="شهر" :value="request('city')" />
        <button class="button-primary self-end">جست‌وجو</button>
    </form>
</section>
<nav aria-label="دسته‌بندی‌ها" class="mb-10 flex flex-wrap gap-3">
    <a class="button-secondary" href="{{ route('home') }}">همه مکان‌ها</a>
    @foreach($categories as $category)<a class="button-secondary" href="{{ route('home', ['category' => $category->id]) }}">{{ $category->name }}</a>@endforeach
</nav>
<section id="places">
    <h2 class="mb-5 text-2xl font-bold">مکان‌های شهر</h2>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($businesses as $business)
        <article class="overflow-hidden rounded-2xl border border-border bg-surface">
            <a href="{{ route('businesses.show', $business->slug) }}">
                @if($business->photos->first())<img class="h-48 w-full object-cover" src="{{ route('media.show', [$business->photos->first(), 'thumbnail' => 1]) }}" alt="{{ $business->name }}" loading="lazy">@else<div class="flex h-36 items-center justify-center bg-soft text-muted">هنوز عکسی ثبت نشده</div>@endif
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
