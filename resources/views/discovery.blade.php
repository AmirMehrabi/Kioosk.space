@extends('layouts.community')
@section('title', 'کشف مکان‌ها'.($city ? ' در '.$city->name : ''))
@section('breadcrumbs')
    <x-breadcrumbs :items="array_values(array_filter([
        ['label' => 'خانه', 'url' => route('home')],
        ['label' => 'کشف', 'url' => route('discovery')],
        $city ? ['label' => $city->name] : null,
        request('category') ? ['label' => $categories->firstWhere('id', (int) request('category'))?->name] : null,
    ]))" />
@endsection
@section('content')
@php
    $categoryIcons = ['رستوران' => 'utensils', 'کافه' => 'coffee', 'خرید' => 'shopping-bag', 'پزشک' => 'medical', 'زیبایی' => 'sparkles', 'خدمات منزل' => 'home', 'گردشگری' => 'compass', 'سایر' => 'grid'];
    $selectedCategory = $categories->firstWhere('id', (int) request('category'));
    $priceOptions = [1 => 'اقتصادی', 2 => 'متوسط', 3 => 'گران', 4 => 'بسیار گران'];
    $ratingOptions = [4 => '۴★ و بیشتر', 3 => '۳★ و بیشتر', 2 => '۲★ و بیشتر'];
    $hasActiveFilters = !empty($activeFilters['price']) || !empty($activeFilters['rating']) || !empty($activeFilters['open_now']) || !empty($activeFilters['features']);
@endphp
{{-- Full-width discovery: map + results side-by-side, filters on top of results --}}
<div id="discovery" data-view="list" class="w-screen max-w-[100vw] mx-[calc(50%-50vw)] overflow-x-hidden">
    <script id="discovery-data" type="application/json">{!! json_encode(['city' => $city?->only(['name', 'latitude', 'longitude']), 'businesses' => $mapBusinesses], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <div class="grid min-h-[calc(100vh-180px)] lg:grid-cols-[1.35fr_1fr] xl:grid-cols-[1.45fr_1fr] 2xl:grid-cols-[1.5fr_1fr]">
        {{-- Results column — scrollable, filters on top --}}
        <div class="min-w-0 bg-canvas lg:border-l lg:border-border">
            {{-- Header + search + categories — compact --}}
            <div class="border-b border-border bg-surface px-4 py-4 sm:px-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-extrabold leading-tight sm:text-2xl">
                            @if($selectedCategory) {{ $selectedCategory->name }} در {{ $city?->name ?? 'شهر' }}
                            @elseif(request('query')) «{{ request('query') }}» در {{ $city?->name ?? 'شهر' }}
                            @else کشف مکان‌ها
                            @endif
                        </h1>
                        <p class="mt-1 text-xs leading-5 text-secondary"><strong class="text-ink">{{ $businesses->total() }} مکان</strong> · {{ $city?->name }} @if(request('query')) · «{{ request('query') }}» @endif</p>
                    </div>
                    <span class="hidden items-center gap-1.5 rounded-full border bg-surface px-3 py-1 text-xs sm:inline-flex"><x-icon name="pin" class="size-3.5 text-pomegranate" />{{ $city?->name }}</span>
                </div>

                <form action="{{ route('discovery') }}#places" class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end" role="search">
                    <div class="relative"><x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-muted" /><input id="discovery-query" class="field !mt-0 ps-10 !min-h-10 !py-2 text-sm" name="query" type="search" value="{{ request('query') }}" placeholder="جست‌وجوی نام، کافه، خیابان…"></div>
                    <div class="flex gap-2">
                        <div class="hidden sm:block"><x-city-select :cities="$cities" id="city-search" name="city" label="شهر" :value="$city?->name" /></div>
                        <button class="button-primary !min-h-10 shrink-0"><x-icon name="search" class="size-4" /><span class="hidden sm:inline">جست‌وجو</span></button>
                    </div>
                    @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                    @foreach(['sort','rating','open_now'] as $k) @if(request()->has($k) && request($k) !== '')<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif @endforeach
                    @foreach((array) request('price', []) as $p)<input type="hidden" name="price[]" value="{{ $p }}">@endforeach
                    @foreach((array) request('features', []) as $f)<input type="hidden" name="features[]" value="{{ $f }}">@endforeach
                </form>

                <nav aria-label="دسته‌بندی‌ها" class="mt-4 flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
                    <a @class(['discovery-category !min-h-9 !px-4 text-xs', 'discovery-category-active' => ! request('category')]) href="{{ route('discovery', array_merge(request()->only('query', 'sort', 'rating', 'open_now'), ['city' => $city?->name, 'price' => request('price'), 'features' => request('features')])) }}#places">همه</a>
                    @foreach($categories as $category)
                        <a @class(['discovery-category !min-h-9 !px-4 text-xs', 'discovery-category-active' => (string) request('category') === (string) $category->id]) href="{{ route('discovery', array_merge(request()->only('query', 'sort', 'rating', 'open_now'), ['city' => $city?->name, 'category' => $category->id, 'price' => request('price'), 'features' => request('features')])) }}#places"><x-icon :name="$categoryIcons[$category->name] ?? 'grid'" class="size-3.5" />{{ $category->name }}</a>
                    @endforeach
                </nav>
            </div>

            {{-- Filters on top of results — 2 rows max --}}
            <div class="border-b border-border bg-soft/50 px-4 py-3 sm:px-6">
                <form method="get" action="{{ route('discovery') }}#places" class="space-y-2">
                    @foreach(['query','city','category','sort'] as $k) @if(request()->has($k) && request($k) !== null && request($k) !== '')<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif @endforeach
                    {{-- Row 1: price + rating + open-now + clear — single horizontal row, no wrap beyond 1 line on desktop --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="hidden text-xs font-bold text-muted lg:inline">فیلتر:</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach($priceOptions as $val => $label)
                                <label class="inline-flex cursor-pointer items-center rounded-full border px-2.5 py-1 text-xs font-bold transition {{ in_array($val, $activeFilters['price'] ?? []) ? 'border-pomegranate bg-pomegranate text-white' : 'border-border bg-surface hover:bg-soft' }}" title="{{ $label }}">
                                    <input type="checkbox" name="price[]" value="{{ $val }}" @checked(in_array($val, $activeFilters['price'] ?? [])) onchange="this.form.submit()" class="sr-only">
                                    <bdi dir="ltr">{{ str_repeat('$', $val) }}</bdi>
                                </label>
                            @endforeach
                        </div>
                        <span class="hidden h-4 w-px bg-border sm:block" aria-hidden="true"></span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach($ratingOptions as $stars => $label)
                                <label class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold transition {{ (($activeFilters['rating'] ?? null) == $stars) ? 'border-pomegranate bg-pomegranate text-white' : 'border-border bg-surface hover:bg-soft' }}">
                                    <input type="radio" name="rating" value="{{ $stars }}" @checked(($activeFilters['rating'] ?? null) == $stars) onchange="this.form.submit()" class="sr-only">
                                    {{ $stars }}★
                                </label>
                            @endforeach
                        </div>
                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ !empty($activeFilters['open_now']) ? 'border-pomegranate bg-pomegranate/10 text-pomegranate' : 'border-border bg-surface' }}">
                            <span class="size-2 rounded-full bg-emerald-500"></span>باز
                            <input type="checkbox" name="open_now" value="1" @checked(!empty($activeFilters['open_now'])) onchange="this.form.submit()" class="sr-only">
                        </label>
                        <a href="{{ route('discovery', ['city' => $city?->name, 'category' => request('category')]) }}#places" class="ms-auto text-xs font-bold text-muted hover:text-pomegranate">پاک کردن</a>
                    </div>
                    {{-- Row 2: amenities — collapsible, collapsed = 1 line summary --}}
                    @if($specifications->isNotEmpty())
                        <details class="group rounded-lg border border-border bg-surface open:bg-canvas">
                            <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2 text-xs font-bold">
                                <span class="flex items-center gap-2"><x-icon name="grid" class="size-4 text-muted" />امکانات @if(!empty($activeFilters['features']))<span class="rounded-full bg-pomegranate px-1.5 py-0.5 text-[10px] text-white">{{ count($activeFilters['features']) }}</span>@endif</span>
                                <span class="flex items-center gap-1 text-muted group-open:hidden">نمایش <x-icon name="chevron-down" class="size-3" /></span>
                                <span class="hidden items-center gap-1 text-muted group-open:flex">بستن <x-icon name="close" class="size-3" /></span>
                            </summary>
                            <div class="border-t border-border p-3">
                                <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
                                    @foreach($specifications as $spec)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-2.5 py-1.5 text-xs hover:bg-soft {{ in_array((string)$spec->id, array_map('strval', $activeFilters['features'] ?? [])) ? 'border-pomegranate bg-pomegranate/5 text-pomegranate' : 'border-transparent bg-surface' }}">
                                            <input type="checkbox" name="features[]" value="{{ $spec->id }}" @checked(in_array((string)$spec->id, array_map('strval', $activeFilters['features'] ?? []))) onchange="this.form.submit()" class="size-3.5 accent-pomegranate">
                                            <x-icon :name="$spec->icon" class="size-3.5 shrink-0 text-muted" />
                                            <span class="truncate font-semibold">{{ $spec->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @endif
                </form>
                @if($hasActiveFilters)
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        @foreach((array) ($activeFilters['price'] ?? []) as $p)<a href="{{ route('discovery', array_merge(request()->all(), ['city' => $city?->name, 'price' => array_values(array_diff($activeFilters['price'], [$p]))])) }}#places" class="rounded-full bg-white px-2.5 py-1 text-xs border"> {{ str_repeat('$', $p) }} ×</a>@endforeach
                        @if(!empty($activeFilters['rating']))<a href="{{ route('discovery', array_merge(request()->except('rating'), ['city' => $city?->name])) }}#places" class="rounded-full bg-white px-2.5 py-1 text-xs border">{{ $activeFilters['rating'] }}★ ×</a>@endif
                        @if(!empty($activeFilters['open_now']))<a href="{{ route('discovery', array_merge(request()->except('open_now'), ['city' => $city?->name])) }}#places" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs border">باز ×</a>@endif
                        @foreach((array) ($activeFilters['features'] ?? []) as $fid) @php $spec = $specifications->firstWhere('id', $fid); @endphp @if($spec)<a href="{{ route('discovery', array_merge(request()->all(), ['city' => $city?->name, 'features' => array_values(array_diff($activeFilters['features'], [$fid]))])) }}#places" class="rounded-full bg-white px-2.5 py-1 text-xs border">{{ $spec->label }} ×</a>@endif @endforeach
                    </div>
                @endif
            </div>

            {{-- Sort + count + view toggle --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-surface px-4 py-3 sm:px-6">
                <p class="text-xs text-secondary"><strong class="text-ink">{{ $businesses->total() }} مکان</strong> @if(request('query')) برای «{{ request('query') }}» @endif · صفحه {{ $businesses->currentPage() }} از {{ $businesses->lastPage() ?: 1 }}</p>
                <div class="flex items-center gap-2">
                    <form method="get" action="{{ route('discovery') }}#places" class="flex items-center gap-2">
                        @foreach(request()->except('sort') as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                            @else
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        <label class="flex items-center gap-1.5 text-xs font-semibold text-secondary" for="sort-select">مرتب‌سازی
                            <select id="sort-select" name="sort" class="field !mt-0 !min-h-8 !py-1 !text-xs" onchange="this.form.submit()">
                                <option value="recommended" @selected($sort === 'recommended')>پیشنهادی</option>
                                <option value="rating" @selected($sort === 'rating')>امتیاز</option>
                                <option value="reviews" @selected($sort === 'reviews')>محبوب‌ترین</option>
                                <option value="newest" @selected($sort === 'newest')>جدیدترین</option>
                            </select>
                        </label>
                    </form>
                    <div data-map-controls hidden class="flex rounded-lg border border-border bg-surface p-1 lg:!hidden" role="group" aria-label="نوع نمایش">
                        <button type="button" data-discovery-view="list" aria-pressed="true" aria-controls="discovery-results" class="discovery-view-button !min-h-8 !px-3 text-xs"><x-icon name="grid" class="size-3.5" />فهرست</button>
                        <button type="button" data-discovery-view="map" aria-pressed="false" aria-controls="discovery-map-panel" class="discovery-view-button !min-h-8 !px-3 text-xs"><x-icon name="pin" class="size-3.5" />نقشه</button>
                    </div>
                </div>
            </div>

            {{-- Results list --}}
            <div id="discovery-results" class="discovery-results min-w-0 bg-canvas px-4 py-4 sm:px-6">
                <div class="space-y-3">
                    @forelse($businesses as $business)
                        @php
                            $photo = $business->featuredPhotos->first() ?? $business->photos->first();
                            $hasLocation = $business->latitude >= 24 && $business->latitude <= 41 && $business->longitude >= 43 && $business->longitude <= 64;
                            $hoursStatus = app(\App\Services\BusinessHours::class)->status($business->weekly_hours);
                            $reviewSnippet = $business->reviews->first();
                        @endphp
                        <article id="result-{{ $business->id }}" data-business-result="{{ $business->id }}" class="discovery-result group flex gap-3 rounded-xl border border-border bg-surface p-3 transition hover:shadow-soft sm:gap-4 sm:p-4">
                            <a href="{{ route('businesses.show', $business->slug) }}" class="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-soft text-muted sm:size-28">
                                @if($photo)<img class="size-full object-cover" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="{{ $business->name }}" loading="lazy">@else<x-icon name="store" class="size-7" />@endif
                                @if($business->is_featured)<span class="absolute start-1 top-1 rounded-full bg-pomegranate px-2 py-0.5 text-[10px] font-bold text-white">ویژه</span>@endif
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    @if($hasLocation)
                                        <button type="button" data-focus-business="{{ $business->id }}" class="text-start text-sm font-extrabold leading-5 hover:text-pomegranate sm:text-base" aria-label="نمایش {{ $business->name }} روی نقشه" aria-pressed="false"><span class="me-1 inline-flex size-5 items-center justify-center rounded-full bg-ink text-[10px] font-bold text-white">{{ $loop->iteration + ($businesses->firstItem() - 1) }}</span>{{ $business->name }}</button>
                                    @else
                                        <a class="text-sm font-extrabold leading-5 hover:text-pomegranate sm:text-base" href="{{ route('businesses.show', $business->slug) }}">{{ $business->name }}</a>
                                    @endif
                                    @if($hoursStatus)
                                        <span @class(['hidden shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold sm:inline-flex', 'bg-emerald-50 text-emerald-700' => $hoursStatus['is_open'], 'bg-rose-50 text-rose-700' => !$hoursStatus['is_open']])>{{ $hoursStatus['text'] }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                    <x-rating :value="$business->reviews_avg_rating ?? 0" size="small" />
                                    <span class="font-bold text-ink">{{ $business->reviews_count ? number_format($business->reviews_avg_rating, 1) : '—' }}</span>
                                    <span class="text-secondary">({{ $business->reviews_count }})</span>
                                    <span class="text-muted">·</span>
                                    <span class="text-secondary">{{ $categories->firstWhere('id', $business->category_id)?->name }}</span>
                                    @if($business->price_range)<span class="text-muted">·</span><x-price-range :value="$business->price_range" class="text-xs" />@endif
                                </div>
                                <p class="mt-1 flex items-center gap-1 text-xs leading-4 text-secondary"><x-icon name="pin" class="size-3 text-muted" />{{ $business->city }} · <span class="truncate">{{ $business->address }}</span></p>
                                @if($business->specifications->isNotEmpty())
                                    <div class="mt-1.5 hidden flex-wrap gap-1 sm:flex">
                                        @foreach($business->specifications->take(3) as $spec)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-soft px-2 py-0.5 text-[11px] font-medium text-secondary"><x-icon :name="$spec->icon" class="size-3" />{{ $spec->label }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($reviewSnippet)
                                    <p class="mt-2 line-clamp-2 rounded-lg bg-soft/60 px-2.5 py-1.5 text-xs leading-5 text-secondary">“{{ \Illuminate\Support\Str::limit($reviewSnippet->body, 110) }}”<span class="ms-1 font-bold text-ink">— {{ $reviewSnippet->author?->name ?? 'کاربر' }}</span></p>
                                @elseif($business->description)
                                    <p class="mt-2 line-clamp-1 text-xs leading-5 text-secondary">{{ \Illuminate\Support\Str::limit($business->description, 90) }}</p>
                                @endif
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <span class="text-[11px] text-muted">@if($hasLocation) روی نام بزن تا روی نقشه ببینی @else موقعیت دقیق ثبت نشده @endif</span>
                                    <a class="inline-flex min-h-7 items-center gap-1 rounded-full bg-ink px-3 text-xs font-bold text-white hover:bg-ink/90" href="{{ route('businesses.show', $business->slug) }}">جزئیات <x-icon name="arrow-left" class="size-3" /></a>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed bg-surface p-8 text-center">
                            <h2 class="font-bold">مکانی با این جست‌وجو پیدا نشد</h2>
                            <p class="mt-2 text-sm text-secondary">فیلترها را سبک کن یا «کافه» را امتحان کن.</p>
                            <a class="button-secondary mt-4" href="{{ route('discovery', ['city' => $city?->name]) }}#places">همه مکان‌ها</a>
                        </div>
                    @endforelse
                </div>
                @if($businesses->hasPages())<div class="mt-4">{{ $businesses->onEachSide(1)->links() }}</div>@endif
            </div>
        </div>

        {{-- Map column — flush to left viewport edge, no left gap --}}
        <div id="discovery-map-panel" class="discovery-map-panel relative min-w-0 overflow-hidden border-t border-border bg-soft lg:sticky lg:top-0 lg:h-[100vh] lg:border-0 lg:border-r lg:border-border">
            <div id="discovery-map" class="h-[52vh] min-h-[320px] w-full lg:h-[100vh] lg:min-h-0" role="region" aria-label="نقشه مکان‌های {{ $city?->name }}"></div>
            <button type="button" data-fit-results hidden class="button-secondary absolute start-3 top-3 z-[500] text-xs shadow-soft"><x-icon name="pin" class="size-3.5" />همه نشان‌ها</button>
            <p data-map-status role="status" class="absolute inset-x-3 bottom-3 z-[500] rounded-lg bg-surface/95 px-3 py-2 text-center text-xs leading-5 shadow-soft">نقشه را لمس کن؛ نشان را بزن.</p>
        </div>
    </div>
</div>
@endsection
