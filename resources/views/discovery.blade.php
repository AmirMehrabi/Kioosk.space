@extends('layouts.community')
@section('title', 'کشف مکان‌ها'.($city ? ' در '.$city->name : ''))
@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => 'کشف مکان‌ها']]" />
@endsection
@section('content')
@php
    $categoryIcons = ['رستوران' => 'utensils', 'کافه' => 'coffee', 'خرید' => 'shopping-bag', 'پزشک' => 'medical', 'زیبایی' => 'sparkles', 'خدمات منزل' => 'home', 'گردشگری' => 'compass', 'سایر' => 'grid'];
    $selectedCategory = $categories->firstWhere('id', (int) request('category'));
    $priceOptions = [1 => 'اقتصادی', 2 => 'متوسط', 3 => 'گران', 4 => 'بسیار گران'];
    $ratingOptions = [4 => '۴ ستاره و بیشتر', 3 => '۳ ستاره و بیشتر', 2 => '۲ ستاره و بیشتر'];
    $hasActiveFilters = !empty($activeFilters['price']) || !empty($activeFilters['rating']) || !empty($activeFilters['open_now']) || !empty($activeFilters['features']);
    $mappedCount = collect($mapBusinesses)->filter(fn ($business) => $business['latitude'] >= 24 && $business['latitude'] <= 41 && $business['longitude'] >= 43 && $business['longitude'] <= 64)->count();
@endphp

<div id="discovery" data-view="list" class="w-screen max-w-[100vw] mx-[calc(50%-50vw)] overflow-x-hidden">
    <script id="discovery-data" type="application/json">{!! json_encode(['city' => $city?->only(['name', 'latitude', 'longitude']), 'businesses' => $mapBusinesses, 'resultStart' => $businesses->firstItem() ?? 1], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <section class="border-y border-border bg-surface px-4 py-7 sm:px-6 sm:py-10" aria-labelledby="discovery-title">
        <div class="mx-auto max-w-4xl text-center">
            <p class="text-sm font-bold text-pomegranate">راهنمای شهر از نگاه آدم‌های واقعی</p>
            <h1 id="discovery-title" class="mt-2 text-2xl font-extrabold sm:text-4xl">جای بعدی‌ات را پیدا کن</h1>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-secondary sm:text-base">نام یک مکان، دسته‌بندی یا محله را بنویس؛ یا از دسته‌ها شروع کن.</p>

            <form action="{{ route('discovery') }}#places" class="mx-auto mt-6 grid max-w-3xl gap-3 rounded-2xl border border-border bg-canvas p-3 shadow-soft sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end" role="search" aria-label="جست‌وجوی مکان‌ها">
                <div class="text-start">
                    <label for="discovery-query" class="text-xs font-bold text-secondary">دنبال چه می‌گردی؟</label>
                    <div class="relative mt-2">
                        <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-muted" />
                        <input id="discovery-query" class="field !mt-0 ps-12" name="query" type="search" maxlength="180" value="{{ request('query') }}" placeholder="مثلاً کافه، برگر یا میدان ونک">
                    </div>
                </div>
                <x-city-select :cities="$cities" id="city-search" name="city" label="در کدام شهر؟" :value="$city?->name" />
                <button class="button-primary min-w-28" type="submit"><x-icon name="search" class="size-4" />پیدا کن</button>
                @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
            </form>

            <nav aria-label="دسته‌بندی‌ها" class="mx-auto mt-6 flex max-w-4xl flex-wrap justify-center gap-2">
                <a @class(['discovery-category', 'discovery-category-active' => !request('category')]) href="{{ route('discovery', ['city' => $city?->name, 'query' => request('query')]) }}#places">همه مکان‌ها</a>
                @foreach($categories as $category)
                    <a @class(['discovery-category', 'discovery-category-active' => (string) request('category') === (string) $category->id]) href="{{ route('discovery', ['city' => $city?->name, 'query' => request('query'), 'category' => $category->id]) }}#places">
                        <x-icon :name="$categoryIcons[$category->name] ?? 'grid'" class="size-4" />{{ $category->name }}
                    </a>
                @endforeach
            </nav>
        </div>
    </section>

    <div id="places" data-discovery-layout @class(['grid min-h-[560px]', 'lg:grid-cols-[minmax(0,1.15fr)_minmax(360px,.85fr)]' => $mappedCount])>
        <div class="min-w-0 bg-canvas lg:border-l lg:border-border">
            <div class="sticky top-0 z-10 border-b border-border bg-surface/95 px-4 py-3 backdrop-blur sm:px-6">
                <div class="mx-auto flex max-w-4xl flex-wrap items-center gap-2">
                    <div class="me-auto">
                        <h2 class="text-base font-extrabold sm:text-lg">
                            @if($selectedCategory) {{ $selectedCategory->name }} در {{ $city?->name }}
                            @elseif(request('query')) نتیجه‌های «{{ request('query') }}» در {{ $city?->name }}
                            @else بهترین مکان‌های {{ $city?->name }}
                            @endif
                        </h2>
                        <p class="mt-0.5 text-xs text-secondary">{{ number_format($businesses->total()) }} مکان پیدا شد</p>
                    </div>

                    @if($mappedCount)
                        <div data-map-controls hidden class="flex rounded-xl border border-border bg-canvas p-1 lg:!hidden" role="group" aria-label="نوع نمایش">
                            <button type="button" data-discovery-view="list" aria-pressed="true" class="discovery-view-button"><x-icon name="grid" class="size-4" />فهرست</button>
                            <button type="button" data-discovery-view="map" aria-pressed="false" class="discovery-view-button"><x-icon name="pin" class="size-4" />نقشه</button>
                        </div>
                        <button type="button" data-toggle-map class="button-secondary !hidden !min-h-10 !px-3 text-xs lg:!inline-flex" aria-pressed="false"><x-icon name="map" class="size-4" /><span data-map-toggle-label>پنهان کردن نقشه</span></button>
                    @endif

                    <details class="group relative" @if($hasActiveFilters) open @endif>
                        <summary class="button-secondary min-h-10 cursor-pointer list-none !px-3 text-xs">
                            <x-icon name="grid" class="size-4" />فیلترها
                            @if($hasActiveFilters)<span class="flex size-5 items-center justify-center rounded-full bg-pomegranate text-[10px] text-white">{{ count((array) ($activeFilters['price'] ?? [])) + count((array) ($activeFilters['features'] ?? [])) + (int) !empty($activeFilters['rating']) + (int) !empty($activeFilters['open_now']) }}</span>@endif
                            <x-icon name="chevron-down" class="size-3 transition group-open:rotate-180" />
                        </summary>
                        <div class="fixed inset-x-3 top-24 z-30 max-h-[calc(100dvh-8rem)] overflow-y-auto rounded-2xl border border-border bg-surface p-5 shadow-soft sm:absolute sm:inset-x-auto sm:end-0 sm:top-full sm:mt-2 sm:w-[34rem]">
                            <form method="get" action="{{ route('discovery') }}#places">
                                @foreach(['query', 'city', 'category', 'sort'] as $key) @if(request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif @endforeach
                                <fieldset>
                                    <legend class="text-sm font-extrabold">بازه قیمت</legend>
                                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        @foreach($priceOptions as $value => $label)
                                            <label class="discovery-filter-option"><input class="size-4 accent-pomegranate" type="checkbox" name="price[]" value="{{ $value }}" @checked(in_array($value, $activeFilters['price'] ?? []))><span><strong class="block text-xs">{{ $label }}</strong><bdi dir="ltr" class="text-xs text-muted">{{ str_repeat('$', $value) }}</bdi></span></label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <fieldset class="mt-5">
                                    <legend class="text-sm font-extrabold">حداقل امتیاز</legend>
                                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                        @foreach($ratingOptions as $value => $label)<label class="discovery-filter-option"><input class="size-4 accent-pomegranate" type="radio" name="rating" value="{{ $value }}" @checked(($activeFilters['rating'] ?? null) == $value)><span class="text-xs font-bold">{{ $label }}</span></label>@endforeach
                                    </div>
                                </fieldset>
                                <label class="mt-5 flex min-h-12 cursor-pointer items-center justify-between rounded-xl border border-border px-4 has-checked:border-pomegranate has-checked:bg-pomegranate/5">
                                    <span class="flex items-center gap-2 text-sm font-bold"><span class="size-2.5 rounded-full bg-emerald-500"></span>فقط مکان‌هایی که الان باز هستند</span>
                                    <input class="size-4 accent-pomegranate" type="checkbox" name="open_now" value="1" @checked(!empty($activeFilters['open_now']))>
                                </label>
                                @if($specifications->isNotEmpty())
                                    <fieldset class="mt-5">
                                        <legend class="text-sm font-extrabold">امکانات</legend>
                                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            @foreach($specifications as $spec)<label class="discovery-filter-option"><input class="size-4 accent-pomegranate" type="checkbox" name="features[]" value="{{ $spec->id }}" @checked(in_array((string) $spec->id, array_map('strval', $activeFilters['features'] ?? [])))><x-icon :name="$spec->icon" class="size-4 text-muted" /><span class="truncate text-xs font-bold">{{ $spec->label }}</span></label>@endforeach
                                        </div>
                                    </fieldset>
                                @endif
                                <div class="mt-6 flex gap-2">
                                    <button class="button-primary flex-1" type="submit">نمایش نتیجه‌ها</button>
                                    <a class="button-secondary" href="{{ route('discovery', ['city' => $city?->name, 'query' => request('query'), 'category' => request('category')]) }}#places">پاک کردن</a>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                @if($hasActiveFilters)
                    <div class="mx-auto mt-3 flex max-w-4xl flex-wrap gap-2" aria-label="فیلترهای فعال">
                        @foreach((array) ($activeFilters['price'] ?? []) as $price)<a class="discovery-active-filter" href="{{ route('discovery', array_merge(request()->except('page'), ['price' => array_values(array_diff($activeFilters['price'], [$price]))])) }}#places">{{ $priceOptions[$price] }} <span aria-hidden="true">×</span></a>@endforeach
                        @if(!empty($activeFilters['rating']))<a class="discovery-active-filter" href="{{ route('discovery', request()->except('rating', 'page')) }}#places">{{ $ratingOptions[$activeFilters['rating']] }} <span aria-hidden="true">×</span></a>@endif
                        @if(!empty($activeFilters['open_now']))<a class="discovery-active-filter" href="{{ route('discovery', request()->except('open_now', 'page')) }}#places">الان باز است <span aria-hidden="true">×</span></a>@endif
                        @foreach((array) ($activeFilters['features'] ?? []) as $featureId)
                            @php
                                $spec = $specifications->firstWhere('id', $featureId);
                            @endphp
                            @if($spec)<a class="discovery-active-filter" href="{{ route('discovery', array_merge(request()->except('page'), ['features' => array_values(array_diff($activeFilters['features'], [$featureId]))])) }}#places">{{ $spec->label }} <span aria-hidden="true">×</span></a>@endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="border-b border-border bg-surface px-4 py-3 sm:px-6">
                <div class="mx-auto flex max-w-4xl items-center justify-between gap-3">
                    <p class="text-xs text-secondary">صفحه {{ $businesses->currentPage() }} از {{ $businesses->lastPage() ?: 1 }}</p>
                    <form method="get" action="{{ route('discovery') }}#places">
                        @foreach(request()->except('sort', 'page') as $key => $value)
                            @if(is_array($value)) @foreach($value as $item)<input type="hidden" name="{{ $key }}[]" value="{{ $item }}">@endforeach
                            @else <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label class="flex items-center gap-2 text-xs font-bold" for="sort-select">مرتب‌سازی
                            <select id="sort-select" name="sort" class="field !mt-0 !min-h-10 !py-1 !text-sm" onchange="this.form.submit()">
                                <option value="recommended" @selected($sort === 'recommended')>پیشنهادی</option>
                                <option value="rating" @selected($sort === 'rating')>بالاترین امتیاز</option>
                                <option value="reviews" @selected($sort === 'reviews')>بیشترین تجربه</option>
                                <option value="newest" @selected($sort === 'newest')>جدیدترین</option>
                            </select>
                        </label>
                    </form>
                </div>
            </div>

            <div id="discovery-results" class="discovery-results px-4 py-5 sm:px-6">
                <div class="mx-auto max-w-4xl space-y-4">
                    @forelse($businesses as $business)
                        @php
                            $photo = $business->featuredPhotos->first() ?? $business->photos->first();
                            $hasLocation = $business->latitude >= 24 && $business->latitude <= 41 && $business->longitude >= 43 && $business->longitude <= 64;
                            $hoursStatus = app(\App\Services\BusinessHours::class)->status($business->weekly_hours);
                            $reviewSnippet = $business->reviews->first();
                        @endphp
                        <article id="result-{{ $business->id }}" data-business-result="{{ $business->id }}" class="discovery-result group rounded-2xl border border-border bg-surface p-3 shadow-[0_2px_12px_rgb(36_27_20_/_4%)] sm:p-4">
                            <div class="flex gap-3 sm:gap-5">
                                <a href="{{ route('businesses.show', $business->slug) }}" class="relative flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-soft text-muted sm:size-36" aria-label="مشاهده {{ $business->name }}">
                                    @if($photo)<img class="size-full object-cover" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="" loading="lazy">@else<x-icon name="store" class="size-8" />@endif
                                    @if($business->is_featured)<span class="absolute start-2 top-2 rounded-full bg-pomegranate px-2 py-1 text-[10px] font-bold text-white">پیشنهاد کیوسک</span>@endif
                                </a>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div><a class="text-base font-extrabold leading-6 hover:text-pomegranate sm:text-lg" href="{{ route('businesses.show', $business->slug) }}">{{ $business->name }}</a><p class="mt-1 text-xs font-semibold text-secondary">{{ $business->category?->name ?? $categories->firstWhere('id', $business->category_id)?->name }} @if($business->price_range) · <span aria-hidden="true">{{ $priceOptions[$business->price_range] }}</span><span class="sr-only">بازه قیمت: {{ $priceOptions[$business->price_range] }}</span>@endif</p></div>
                                        @if($hoursStatus)<span @class(['shrink-0 rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $hoursStatus['is_open'], 'bg-rose-50 text-rose-700' => !$hoursStatus['is_open']])>{{ $hoursStatus['text'] }}</span>@endif
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-sm">
                                        @if($business->reviews_count)<x-rating :value="$business->reviews_avg_rating" size="small" /><strong>{{ number_format($business->reviews_avg_rating, 1) }}</strong><span class="text-secondary">از {{ number_format($business->reviews_count) }} تجربه</span>
                                        @else<span class="rounded-full bg-soft px-2.5 py-1 text-xs text-secondary">هنوز امتیازی ندارد</span>@endif
                                    </div>
                                    <p class="mt-2 flex items-start gap-1.5 text-xs leading-5 text-secondary"><x-icon name="pin" class="mt-0.5 size-4 shrink-0 text-muted" /><span>{{ $business->address ?: $business->city }}</span></p>
                                    @if($reviewSnippet)<p class="mt-3 hidden line-clamp-2 rounded-xl bg-soft/70 px-3 py-2 text-xs leading-5 text-secondary sm:block">«{{ \Illuminate\Support\Str::limit($reviewSnippet->body, 110) }}»</p>
                                    @elseif($business->description)<p class="mt-3 hidden line-clamp-2 text-xs leading-5 text-secondary sm:block">{{ \Illuminate\Support\Str::limit($business->description, 110) }}</p>@endif
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-end gap-2 border-t border-border pt-3">
                                @if($hasLocation)<button type="button" data-focus-business="{{ $business->id }}" class="button-secondary !min-h-10 !px-3 text-xs" aria-label="نمایش {{ $business->name }} روی نقشه" aria-pressed="false"><x-icon name="pin" class="size-4" />روی نقشه</button>
                                @else<span class="me-auto text-xs text-muted">موقعیت روی نقشه ثبت نشده</span>@endif
                                <a class="button-primary !min-h-10 !px-4 text-xs" href="{{ route('businesses.show', $business->slug) }}">دیدن جزئیات <x-icon name="arrow-left" class="size-3" /></a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-border bg-surface px-6 py-12 text-center">
                            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-soft text-muted"><x-icon name="search" class="size-6" /></span>
                            <h2 class="mt-4 text-lg font-extrabold">مکانی با این جست‌وجو پیدا نشد</h2>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-secondary">املای عبارت را بررسی کن، فیلترها را بردار یا شهر دیگری را انتخاب کن.</p>
                            <div class="mt-5 flex flex-wrap justify-center gap-2"><a class="button-primary" href="{{ route('discovery', ['city' => $city?->name]) }}#places">دیدن همه مکان‌های {{ $city?->name }}</a><a class="button-secondary" href="#discovery-query">جست‌وجوی تازه</a></div>
                        </div>
                    @endforelse
                    @if($businesses->hasPages())<div class="pt-2">{{ $businesses->onEachSide(1)->links() }}</div>@endif
                </div>
            </div>
        </div>

        @if($mappedCount)
            <aside id="discovery-map-panel" class="discovery-map-panel relative min-w-0 overflow-hidden border-t border-border bg-soft lg:sticky lg:top-0 lg:h-screen lg:border-t-0" aria-label="نقشه نتیجه‌ها">
                <div id="discovery-map" class="h-[calc(100dvh-9rem)] min-h-[360px] w-full lg:h-screen" role="region" aria-label="{{ $mappedCount }} مکان از نتیجه‌های این صفحه روی نقشه"></div>
                <button type="button" data-fit-results hidden class="button-secondary absolute start-3 top-3 z-[500] text-xs shadow-soft"><x-icon name="pin" class="size-3.5" />نمایش همه نشان‌ها</button>
                <p data-map-status role="status" class="absolute inset-x-3 bottom-3 z-[500] rounded-xl bg-surface/95 px-3 py-2 text-center text-xs leading-5 shadow-soft"></p>
            </aside>
        @endif
    </div>
</div>
@endsection
