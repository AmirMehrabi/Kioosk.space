@extends('layouts.community')
@section('title', 'کشف مکان‌ها'.($city ? ' در '.$city->name : ''))
@section('content')
<div id="discovery" data-view="list">
    <script id="discovery-data" type="application/json">{!! json_encode(['city' => $city?->only(['name', 'latitude', 'longitude']), 'businesses' => $mapBusinesses], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <header class="mb-5 flex items-end justify-between gap-4">
        <div><h1 class="text-2xl font-extrabold sm:text-3xl">جای بعدی‌ات را پیدا کن.</h1><p class="mt-2 text-sm leading-7 text-secondary">مکان‌های {{ $city?->name ?? 'شهر' }}، با تجربه‌های واقعی مردم.</p></div>
        <span class="hidden items-center gap-2 rounded-full border border-border bg-surface px-4 py-2 text-sm sm:inline-flex"><x-icon name="pin" class="text-pomegranate" />{{ $city?->name }}</span>
    </header>

    <form action="{{ route('discovery') }}#places" class="relative z-20 grid grid-cols-[1fr_auto] items-end gap-3 rounded-2xl border border-border bg-surface p-3 sm:grid-cols-[minmax(0,1fr)_minmax(10rem,16rem)_auto] sm:p-4" role="search">
        <div class="col-span-2 sm:col-span-1">
            <label class="text-sm font-semibold" for="discovery-query">دنبال چه می‌گردی؟</label>
            <div class="relative"><x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-muted" /><input id="discovery-query" class="field ps-10" name="query" type="search" maxlength="180" value="{{ request('query') }}" placeholder="نام مکان، کافه، خیابان…"></div>
        </div>
        <x-city-select :cities="$cities" id="city-search" name="city" label="شهر" :value="$city?->name" />
        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
        <button class="button-primary min-h-12"><x-icon name="search" /><span>جست‌وجو</span></button>
    </form>

    <nav aria-label="دسته‌بندی‌ها" class="my-5 flex gap-2 overflow-x-auto pb-2">
        <a @class(['discovery-category', 'discovery-category-active' => ! request('category')]) href="{{ route('discovery', [...request()->only('query'), 'city' => $city?->name]) }}#places" @unless(request('category')) aria-current="true" @endunless>همه مکان‌ها</a>
        @foreach($categories as $category)
            <a @class(['discovery-category', 'discovery-category-active' => (string) request('category') === (string) $category->id]) href="{{ route('discovery', [...request()->only('query'), 'city' => $city?->name, 'category' => $category->id]) }}#places" @if((string) request('category') === (string) $category->id) aria-current="true" @endif>{{ $category->name }}</a>
        @endforeach
    </nav>

    <section id="places" class="scroll-mt-4" aria-label="نتایج جست‌وجو">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-secondary"><strong class="text-ink">{{ $businesses->total() }} مکان</strong> در {{ $city?->name }} @if(request('query')) برای «{{ request('query') }}» @endif</p>
            <div data-map-controls hidden class="flex rounded-xl border border-border bg-surface p-1 lg:!hidden" role="group" aria-label="نوع نمایش">
                <button type="button" data-discovery-view="list" aria-pressed="true" aria-controls="discovery-results" class="discovery-view-button"><x-icon name="grid" />فهرست</button>
                <button type="button" data-discovery-view="map" aria-pressed="false" aria-controls="discovery-map-panel" class="discovery-view-button"><x-icon name="pin" />نقشه</button>
            </div>
        </div>

        <div class="discovery-workspace grid gap-4 lg:grid-cols-[minmax(20rem,2fr)_minmax(0,3fr)]">
            <div id="discovery-results" class="discovery-results min-w-0 rounded-2xl border border-border bg-surface lg:max-h-[min(72dvh,850px)] lg:overflow-y-auto">
                <div class="divide-y divide-border">
                    @forelse($businesses as $business)
                        @php
                            $photo = $business->featuredPhotos->first() ?? $business->photos->first();
                            $hasLocation = $business->latitude >= 24 && $business->latitude <= 41 && $business->longitude >= 43 && $business->longitude <= 64;
                        @endphp
                        <article id="result-{{ $business->id }}" data-business-result="{{ $business->id }}" class="discovery-result scroll-m-3 p-4 sm:p-5">
                            <div class="flex gap-4">
                                <a href="{{ route('businesses.show', $business->slug) }}" class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-soft text-muted sm:size-24" aria-label="صفحه {{ $business->name }}">
                                    @if($photo)<img class="size-full object-cover" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="{{ $business->name }}" loading="lazy">@else<x-icon name="store" class="size-7" />@endif
                                </a>
                                <div class="min-w-0 flex-1">
                                    @if($hasLocation)
                                        <button type="button" data-focus-business="{{ $business->id }}" class="min-h-11 text-start text-lg font-bold leading-7 hover:text-pomegranate" aria-label="نمایش {{ $business->name }} روی نقشه" aria-pressed="false"><span class="me-1 text-sm text-pomegranate">{{ $loop->iteration }}.</span> {{ $business->name }}</button>
                                    @else
                                        <a class="inline-flex min-h-11 items-center text-lg font-bold leading-7 hover:text-pomegranate" href="{{ route('businesses.show', $business->slug) }}">{{ $business->name }}</a>
                                    @endif
                                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-secondary">
                                        @if($business->reviews_count)<span class="font-bold text-pomegranate">★ {{ number_format($business->reviews_avg_rating, 1) }}</span><span>({{ $business->reviews_count }} تجربه)</span>@else<span>هنوز امتیازی ندارد</span>@endif
                                        <span aria-hidden="true">·</span><span>{{ $categories->firstWhere('id', $business->category_id)?->name }}</span>
                                        <x-price-range :value="$business->price_range" />
                                    </p>
                                    <p class="mt-2 text-sm leading-6 text-secondary">{{ $business->address }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-2 text-xs">
                                @if($hasLocation)<span class="inline-flex items-center gap-1 text-muted"><x-icon name="pin" class="size-4" /><span>نمایش روی نقشه با انتخاب نام</span></span>@else<span class="text-muted">موقعیت دقیق ثبت نشده</span>@endif
                                <a class="inline-flex min-h-11 shrink-0 items-center gap-1 font-semibold text-pomegranate" href="{{ route('businesses.show', $business->slug) }}">جزئیات مکان<x-icon name="arrow-left" class="size-4" /></a>
                            </div>
                        </article>
                    @empty
                        <div class="flex min-h-72 flex-col items-center justify-center p-7 text-center">
                            <span class="mb-4 rounded-2xl bg-soft p-4 text-muted"><x-icon name="search" class="size-7" /></span>
                            <h2 class="text-lg font-bold">مکانی با این جست‌وجو پیدا نشد</h2>
                            <p class="mt-3 text-sm leading-7 text-secondary">عبارت کوتاه‌تری بنویس یا دسته‌بندی دیگری را انتخاب کن.</p>
                            <a class="button-secondary mt-5" href="{{ route('discovery', ['city' => $city?->name]) }}#places">همه مکان‌های شهر</a>
                            <a class="mt-4 inline-flex min-h-11 items-center text-sm text-pomegranate" href="{{ route('contribute') }}">جای خوبی می‌شناسی؟ معرفی کن</a>
                        </div>
                    @endforelse
                </div>
                @if($businesses->hasPages())<div class="border-t border-border p-4">{{ $businesses->onEachSide(0)->links() }}</div>@endif
            </div>

            <div id="discovery-map-panel" class="discovery-map-panel relative isolate min-w-0 overflow-hidden rounded-2xl border border-border bg-soft">
                <div id="discovery-map" class="h-[62dvh] min-h-80 w-full lg:h-[min(72dvh,850px)]" role="region" aria-label="نقشه مکان‌های {{ $city?->name }}"></div>
                <button type="button" data-fit-results hidden class="button-secondary absolute start-3 top-3 z-[500] shadow-soft"><x-icon name="pin" class="size-4" />نمایش همه نشان‌ها</button>
                <p data-map-status role="status" class="absolute inset-x-3 bottom-8 z-[500] rounded-xl bg-surface/95 px-4 py-3 text-center text-xs leading-6 shadow-soft">برای نمایش نقشه، جاوااسکریپت و اتصال اینترنت لازم است.</p>
            </div>
        </div>
        <p class="mt-3 text-xs leading-6 text-muted">نشان‌ها مربوط به مکان‌های همین صفحه‌اند. مکان‌های بدون موقعیت دقیق در فهرست نمایش داده می‌شوند.</p>
    </section>
</div>
@endsection
