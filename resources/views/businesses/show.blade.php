<!DOCTYPE html>
<html lang="fa" dir="rtl" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $business['name'] }} در {{ $business['neighborhood'] }}؛ تصاویر، اطلاعات و نظرهای کاربران در کیوسک. صفحه نمونه نمایشی.">
    <title>{{ $business['name'] }} | عکس‌ها و نظرها — کیوسک</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas font-sans text-ink antialiased selection:bg-pomegranate selection:text-white">
<div x-data="businessPage({{ Illuminate\Support\Js::from($business) }}, {{ Illuminate\Support\Js::from(['name' => auth()->user()?->name ?? 'شما', 'id' => auth()->id() ?? 'guest']) }})" @keydown.window="galleryKey($event)" class="min-h-screen">
    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex h-20 max-w-[1240px] items-center gap-6 px-4 md:px-6">
            <a href="{{ route('home') }}" class="shrink-0 text-[28px] font-extrabold" aria-label="صفحه اصلی کیوسک">کیوسک<span class="text-pomegranate">.</span></a>
            <form action="{{ route('home') }}" method="GET" class="hidden max-w-lg flex-1 items-center rounded-xl border border-border bg-canvas p-1 sm:flex">
                <x-icon name="search" class="mr-3 text-muted"/><input name="query" type="search" placeholder="کافه، رستوران، هر جای خوب…" aria-label="جست‌وجوی کسب‌وکار" class="min-w-0 flex-1 bg-transparent px-3 py-2 text-sm outline-none">
                <span class="hidden border-r border-border px-4 text-xs text-secondary md:block">تهران</span><button type="submit" class="rounded-lg bg-pomegranate p-3 text-white" aria-label="جست‌وجو"><x-icon name="search" class="size-4"/></button>
            </form>
            <div class="mr-auto flex items-center gap-5 text-sm"><a href="{{ route('business.login') }}" class="hidden text-secondary hover:text-pomegranate lg:block">برای کسب‌وکارها</a><a href="{{ route(auth()->check() ? 'account' : 'login') }}" class="rounded-xl border border-border bg-surface px-4 py-3 font-semibold hover:border-muted">{{ auth()->check() ? 'حساب من' : 'ورود / ثبت‌نام' }}</a></div>
        </div>
    </header>
    <main class="mx-auto max-w-[1240px] px-4 pb-20 md:px-6">
        <div class="flex items-center justify-between gap-3 py-5 text-xs text-muted">
            <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-2"><a href="{{ route('home') }}" class="hover:text-pomegranate">خانه</a><x-icon name="chevron-left" class="size-3"/><span>تهران</span><x-icon name="chevron-left" class="size-3"/><span>{{ $business['category'] }}</span><x-icon name="chevron-left" class="size-3"/><span class="text-secondary">{{ $business['name'] }}</span></nav>
            <span class="shrink-0 rounded-md bg-soft px-2 py-1 text-[10px]">صفحه نمونه</span>
        </div>

        <section aria-label="گالری تصاویر {{ $business['name'] }}" class="relative grid h-[280px] grid-cols-3 grid-rows-2 gap-2 overflow-hidden rounded-2xl sm:h-[360px] lg:h-[390px] lg:grid-cols-12 lg:gap-3">
            <button type="button" @click="openGallery(0)" class="group relative col-span-2 row-span-2 overflow-hidden bg-soft text-right lg:col-span-7" aria-label="نمایش عکس اصلی">
                <img src="{{ $business['gallery'][0]['src'] }}" alt="{{ $business['gallery'][0]['caption'] }}؛ {{ $business['name'] }}" width="1400" height="1000" fetchpriority="high" class="h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105">
                <span class="absolute right-5 bottom-5 rounded-lg bg-ink/60 px-3 py-2 text-xs text-white backdrop-blur-sm">{{ $business['gallery'][0]['caption'] }}</span>
            </button>
            <button type="button" @click="openGallery(1)" class="group relative overflow-hidden bg-soft lg:col-span-3 lg:row-span-2" aria-label="نمایش عکس {{ $business['gallery'][1]['caption'] }}"><img src="{{ $business['gallery'][1]['src'] }}" alt="{{ $business['gallery'][1]['caption'] }}" width="700" height="1000" class="h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105"><span class="absolute inset-x-3 bottom-4 hidden text-center text-[10px] tracking-[0.18em] text-white drop-shadow-lg lg:block" dir="ltr">{{ $business['english'] }}</span></button>
            <button type="button" @click="openGallery(2)" class="group relative overflow-hidden bg-soft lg:col-span-2" aria-label="نمایش عکس {{ $business['gallery'][2]['caption'] }}"><img src="{{ $business['gallery'][2]['src'] }}" alt="{{ $business['gallery'][2]['caption'] }}" width="500" height="400" class="h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105"></button>
            <button type="button" @click="openGallery(3)" class="group relative hidden overflow-hidden bg-soft lg:col-span-2 lg:block" aria-label="نمایش عکس {{ $business['gallery'][3]['caption'] }}"><img src="{{ $business['gallery'][3]['src'] }}" alt="{{ $business['gallery'][3]['caption'] }}" width="500" height="400" class="h-full w-full object-cover transition duration-500 motion-safe:group-hover:scale-105"></button>
            <button type="button" @click="openGallery(0)" class="absolute bottom-4 left-4 flex min-h-11 items-center gap-2 rounded-xl border border-white/50 bg-white px-4 text-xs font-semibold shadow-soft hover:bg-soft"><x-icon name="grid" class="size-4"/> همه {{ strtr((string) count($business['gallery']), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶']) }} عکس</button>
        </section>

        <section class="flex flex-col justify-between gap-6 py-8 md:flex-row md:items-center">
            <div>
                <div class="mb-3 flex items-center gap-3 text-xs"><span class="text-pomegranate">{{ $business['category'] }}</span><span class="text-border">/</span><span class="text-secondary">تهران، {{ $business['neighborhood'] }}</span></div>
                <h1 class="flex items-center gap-3 text-3xl font-extrabold md:text-[38px]">{{ $business['name'] }} <span title="اطلاعات نمایشی"><x-icon name="badge" class="size-6 text-positive"/></span></h1>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm"><x-rating :value="$business['rating']" dynamic="average"/><strong x-text="fa(average)">۴٫۸</strong><a href="#reviews" class="border-b border-muted text-secondary"><span x-text="fa(reviews.length)">۱۰</span> نظر</a><span class="text-border">|</span><span class="text-muted">قیمت {{ $business['price'] }}</span></div>
                <p class="mt-4 text-sm text-secondary">{{ $business['tagline'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2 md:pt-6">
                <button type="button" @click="openReview()" class="flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-pomegranate px-5 text-sm font-bold text-white hover:bg-pomegranate-dark md:flex-none"><x-icon name="edit" class="size-4"/> نوشتن نظر</button>
                <button type="button" @click="toggleSaved()" :aria-pressed="saved" :class="saved ? 'border-pomegranate text-pomegranate' : 'border-border text-secondary'" class="flex min-h-12 items-center gap-2 rounded-xl border bg-surface px-4 text-sm"><x-icon name="heart" class="size-4"/><span x-text="saved ? 'ذخیره شد' : 'ذخیره'">ذخیره</span></button>
                <button type="button" @click="share()" class="flex min-h-12 items-center justify-center rounded-xl border border-border bg-surface px-4 text-secondary hover:border-muted" aria-label="اشتراک‌گذاری"><x-icon name="share" class="size-4"/></button>
            </div>
        </section>

        <nav aria-label="بخش‌های صفحه" class="sticky top-0 z-20 -mx-4 flex gap-6 overflow-x-auto border-y border-border bg-canvas/95 px-4 text-sm backdrop-blur-sm md:mx-0 md:gap-9 md:px-0">
            <a href="#overview" class="whitespace-nowrap border-b-2 border-pomegranate py-4 font-semibold text-pomegranate">درباره اینجا</a><a href="#reviews" class="whitespace-nowrap py-4 text-secondary hover:text-pomegranate">نظرها <span class="mr-1 text-xs text-muted" x-text="fa(reviews.length)">۱۰</span></a><button type="button" @click="openGallery(0)" class="whitespace-nowrap py-4 text-secondary hover:text-pomegranate">عکس‌ها</button><a href="#location" class="whitespace-nowrap py-4 text-secondary hover:text-pomegranate">آدرس و ساعت کاری</a>
        </nav>

        <div class="grid items-start gap-8 pt-9 lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-12">
            <div class="min-w-0">
                <section id="overview" class="scroll-mt-24 pb-9">
                    <h2 class="text-xl font-bold">کمی درباره {{ $business['name'] }}</h2>
                    <p class="mt-4 text-sm leading-8 text-secondary">{{ $business['description'] }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">@foreach ($business['specialties'] as $specialty)<span class="rounded-lg border border-border bg-surface px-3 py-2 text-xs text-secondary">{{ $specialty }}</span>@endforeach</div>
                    <div class="mt-7 grid grid-cols-2 gap-x-4 gap-y-5 border-t border-border pt-6 sm:grid-cols-3">@foreach ($business['amenities'] as $icon => $amenity)<div class="flex items-center gap-3 text-xs text-secondary"><x-icon :name="$icon" class="size-5 text-muted"/>{{ $amenity }}</div>@endforeach</div>
                </section>

                <section id="reviews" class="scroll-mt-24 border-t border-border pt-8">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-xl font-bold">از نگاه آدم‌هایی که اینجا بوده‌اند</h2><x-icon name="message" class="hidden text-muted sm:block"/></div>
                    <p class="mt-2 text-xs text-muted">تجربه‌های کوچک، انتخاب‌های بهتر.</p>
                    <div class="mt-6 flex flex-col gap-6 rounded-2xl border border-border bg-surface p-6 sm:flex-row sm:items-center sm:gap-8">
                        <div class="flex shrink-0 flex-col items-center gap-3 sm:w-36"><strong class="text-5xl font-extrabold tracking-tight" x-text="fa(average)">۴٫۸</strong><x-rating :value="$business['rating']" dynamic="average"/><p class="text-xs text-muted">از <span x-text="fa(reviews.length)">۱۰</span> نظر</p></div>
                        <div class="flex-1 space-y-1.5">
                            @foreach ([5,4,3,2,1] as $rating)
                            <button type="button" @click="setRating({{ $rating }})" :aria-pressed="ratingFilter === {{ $rating }}" class="group flex min-h-8 w-full items-center gap-3 rounded-md px-1 text-xs text-secondary hover:bg-soft" aria-label="فیلتر نظرهای {{ $rating }} ستاره"><span class="w-3">{{ ['۱','۲','۳','۴','۵'][$rating-1] }}</span><progress :value="ratingCount({{ $rating }})" :max="reviews.length || 1" value="{{ $rating === 5 ? 9 : ($rating === 3 ? 1 : 0) }}" max="10" class="rating-progress h-2 flex-1 overflow-hidden rounded-full"></progress><span class="w-5 text-left text-muted" x-text="fa(ratingCount({{ $rating }}))"></span></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-6 flex items-start gap-3 rounded-xl bg-soft p-4 text-xs leading-6 text-secondary"><x-icon name="badge" class="mt-0.5 size-5 text-muted"/><p>هر تجربه ارزش شنیدن دارد. نظرهای مثبت و منفی را بخوانید و اگر اینجا بوده‌اید، تجربه خودتان را هم بنویسید.</p></div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <label class="flex flex-1 items-center gap-2 rounded-xl border border-border bg-surface px-3"><x-icon name="search" class="size-4 text-muted"/><input type="search" x-model.debounce.150ms="search" @input="visibleCount = 4" placeholder="جست‌وجو در تجربه‌ها…" aria-label="جست‌وجو در نظرها" class="min-h-12 min-w-0 w-full bg-transparent text-sm outline-none"></label>
                        <label class="flex items-center gap-2 rounded-xl border border-border bg-surface px-3 text-xs text-muted">ترتیب:<select x-model="sort" @change="visibleCount = 4" aria-label="مرتب‌سازی نظرها" class="min-h-12 flex-1 bg-transparent pl-3 text-sm text-ink outline-none"><option value="helpful">مفیدترین</option><option value="newest">جدیدترین</option><option value="highest">بیشترین امتیاز</option><option value="lowest">کمترین امتیاز</option></select></label>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <button type="button" @click="ratingFilter = 0; photosOnly = false; visibleCount = 4" :class="!ratingFilter && !photosOnly ? 'border-ink bg-ink text-white' : 'border-border bg-surface text-secondary'" class="min-h-10 rounded-lg border px-3">همه نظرها</button>
                        <button type="button" @click="photosOnly = !photosOnly; visibleCount = 4" :aria-pressed="photosOnly" :class="photosOnly ? 'border-pomegranate text-pomegranate' : 'border-border text-secondary'" class="flex min-h-10 items-center gap-2 rounded-lg border bg-surface px-3"><x-icon name="camera" class="size-4"/> همراه عکس</button>
                        <button type="button" x-show="ratingFilter" x-cloak @click="ratingFilter = 0" class="flex min-h-10 items-center gap-2 rounded-lg border border-pomegranate bg-surface px-3 text-pomegranate"><span x-text="fa(ratingFilter) + ' ستاره'"></span><x-icon name="close" class="size-3"/></button>
                        <span class="mr-auto py-2 text-muted"><span x-text="fa(filteredReviews.length)">۱۰</span> نظر</span>
                    </div>

                    <div class="mt-5 divide-y divide-border" id="review-list">
                        <template x-for="review in filteredReviews.slice(0, visibleCount)" :key="review.id">
                            <article class="py-7 first:pt-3" :id="'review-' + review.id">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3"><span class="flex size-11 shrink-0 items-center justify-center rounded-full border border-border bg-soft font-bold text-secondary" x-text="review.initials"></span><div><h3 class="text-sm font-bold" x-text="review.author"></h3><p class="mt-1 text-[11px] text-muted"><span x-text="review.own ? 'نظر شما · نمایشی' : fa(review.reviewerCount) + ' نظر در کیوسک'"></span></p></div></div>
                                    <time class="pt-1 text-[11px] text-muted" :datetime="review.date" x-text="date(review.date)"></time>
                                </div>
                                <div class="mt-4 flex flex-wrap items-center gap-3"><span class="inline-flex gap-1" role="img" :aria-label="fa(review.rating) + ' از ۵ ستاره'"><template x-for="star in 5" :key="star"><span :class="star <= review.rating ? 'bg-pomegranate' : 'bg-border'" class="flex size-6 items-center justify-center rounded-[5px] text-white"><x-icon name="star" :filled="true" class="size-4"/></span></template></span><span class="text-[11px] text-muted" x-text="review.visit"></span></div>
                                <h4 class="mt-4 text-sm font-bold" x-text="review.title"></h4><p class="mt-2 whitespace-pre-line text-sm leading-8 text-secondary" x-text="review.body"></p>
                                <div x-show="review.photos.length" class="mt-4 flex gap-2"><template x-for="(photo, index) in review.photos" :key="index"><button type="button" @click="openPhotos(review.photos, index)" class="overflow-hidden rounded-xl" :aria-label="'نمایش عکس نظر ' + review.author"><img :src="photo.src" :alt="photo.caption || 'عکس همراه نظر'" loading="lazy" class="h-24 w-28 object-cover transition hover:opacity-80 sm:h-28 sm:w-36"></button></template></div>
                                <div x-show="review.ownerReply" class="mt-5 rounded-xl border-r-2 border-positive bg-soft p-4"><div class="mb-2 flex items-center gap-2 text-xs font-bold"><x-icon name="store" class="size-4 text-positive"/> پاسخ {{ $business['name'] }}<span class="mr-auto text-[10px] font-normal text-muted">صاحب کسب‌وکار</span></div><p class="text-xs leading-7 text-secondary" x-text="review.ownerReply"></p></div>
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <button type="button" @click="toggleHelpful(review.id)" :aria-pressed="!!helpful[review.id]" :class="helpful[review.id] ? 'border-pomegranate text-pomegranate' : 'border-border text-secondary'" class="flex min-h-10 items-center gap-2 rounded-lg border bg-surface px-3 text-xs"><x-icon name="thumb" class="size-4"/> مفید بود <span x-text="fa(review.helpful + (helpful[review.id] ? 1 : 0))"></span></button>
                                    <button type="button" @click="toggleComments(review.id)" :aria-expanded="!!expandedComments[review.id]" :aria-controls="'comments-' + review.id" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-xs text-secondary hover:bg-soft"><x-icon name="message" class="size-4"/> گفت‌وگو <span x-show="review.comments.length" x-text="fa(review.comments.length)"></span></button>
                                    <button type="button" x-show="!review.own" @click="openReport(review.id)" class="mr-auto rounded-lg p-3 text-muted hover:text-pomegranate" aria-label="گزارش نظر"><x-icon name="flag" class="size-4"/></button>
                                    <button type="button" x-show="review.own" @click="openReview(review)" class="mr-auto rounded-lg p-3 text-muted hover:text-pomegranate" aria-label="ویرایش نظر شما"><x-icon name="edit" class="size-4"/></button>
                                    <button type="button" x-show="review.own" @click="askDelete(review.id)" class="rounded-lg p-3 text-muted hover:text-pomegranate" aria-label="حذف نظر شما"><x-icon name="trash" class="size-4"/></button>
                                </div>
                                <div x-show="expandedComments[review.id]" :id="'comments-' + review.id" class="mt-5 space-y-4 border-r border-border pr-4 sm:pr-6">
                                    <template x-for="comment in review.comments" :key="comment.id"><div><div class="flex items-center justify-between gap-2"><strong class="text-xs" x-text="comment.author"></strong><button type="button" x-show="comment.own" @click="deleteComment(review.id, comment.id)" class="rounded-md p-2 text-muted hover:text-pomegranate" aria-label="حذف پاسخ شما"><x-icon name="trash" class="size-3"/></button></div><p class="mt-1 whitespace-pre-line text-xs leading-7 text-secondary" x-text="comment.body"></p><button type="button" @click="replyTo(review.id, comment.author)" class="min-h-9 text-[11px] text-muted hover:text-pomegranate">پاسخ دادن</button></div></template>
                                    <form @submit.prevent="addComment(review.id)" class="rounded-xl border border-border bg-surface p-3"><label :for="'comment-input-' + review.id" class="mb-2 block text-xs font-semibold">شما چه فکر می‌کنید؟</label><textarea :id="'comment-input-' + review.id" x-model="commentDrafts[review.id]" rows="2" maxlength="500" required placeholder="یک پاسخ محترمانه بنویسید…" class="w-full resize-y bg-transparent text-xs leading-7 outline-none"></textarea><p x-show="commentErrors[review.id]" x-text="commentErrors[review.id]" role="alert" class="text-xs text-pomegranate"></p><div class="mt-2 flex items-center justify-between"><span class="text-[10px] text-muted">پاسخ نمایشی، فقط در این مرورگر</span><button type="submit" class="min-h-10 rounded-lg bg-ink px-4 text-xs font-semibold text-white">ارسال پاسخ</button></div></form>
                                </div>
                            </article>
                        </template>
                    </div>
                    <div x-show="!filteredReviews.length" x-cloak class="my-6 rounded-2xl border border-dashed border-border p-8 text-center"><x-icon name="search" class="mx-auto size-7 text-muted"/><h3 class="mt-4 font-bold">نظری با این فیلتر پیدا نشد</h3><p class="mt-2 text-xs leading-7 text-muted">عبارت دیگری جست‌وجو کنید یا فیلترها را بردارید.</p><button type="button" @click="resetFilters()" class="mt-4 min-h-11 text-sm font-semibold text-pomegranate">نمایش همه نظرها</button></div>
                    <button type="button" x-show="visibleCount < filteredReviews.length" @click="visibleCount += 4" class="mt-4 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-border bg-surface text-sm font-semibold hover:border-muted">نمایش نظرهای بیشتر <x-icon name="chevron-down" class="size-4"/></button>
                    <noscript><p class="mt-6 rounded-xl bg-soft p-4 text-sm leading-7">برای فیلتر کردن نظرها و نوشتن نظر، جاوااسکریپت مرورگر را فعال کنید.</p>@foreach (array_slice($business['reviews'], 0, 4) as $review)<article class="border-b border-border py-6"><h3 class="font-bold">{{ $review['author'] }}</h3><x-rating :value="$review['rating']" class="mt-3"/><h4 class="mt-3 font-semibold">{{ $review['title'] }}</h4><p class="mt-2 text-sm leading-8">{{ $review['body'] }}</p></article>@endforeach</noscript>
                </section>
            </div>

            <aside id="location" class="scroll-mt-24 space-y-5 lg:sticky lg:top-24">
                <section class="overflow-hidden rounded-2xl border border-border bg-surface">
                    <div class="relative h-40 overflow-hidden bg-soft" aria-label="تصویر نمادین موقعیت محله، نقشه دقیق کسب‌وکار نیست">
                        <svg viewBox="0 0 340 160" class="h-full w-full" aria-hidden="true"><rect width="340" height="160" fill="#F3EFE9"/><path d="M-20 35 360 95M-20 130 350 15M70-20 120 190M230-20 190 190" stroke="#fff" stroke-width="17"/><path d="m-20 35 380 60M-20 130 370-115M70-20l50 210M230-20l-40 210" stroke="#E5E0D8" stroke-width="1"/><rect x="265" y="99" width="65" height="46" rx="14" fill="#E5E0D8"/><rect x="12" y="53" width="45" height="32" rx="9" fill="#E5E0D8"/></svg>
                        <div class="absolute inset-0 flex items-center justify-center"><span class="flex size-12 items-center justify-center rounded-full border-4 border-white bg-pomegranate text-white shadow-soft"><x-icon name="pin" class="size-5"/></span></div><span class="absolute bottom-3 right-3 rounded-md bg-white/90 px-2 py-1 text-[10px] text-secondary">{{ $business['neighborhood'] }} · موقعیت نمادین</span>
                    </div>
                    <div class="p-5"><h2 class="font-bold">آدرس و اطلاعات تماس</h2><p class="mt-3 text-xs leading-7 text-secondary">{{ $business['address'] }}</p><p class="mt-1 text-[10px] text-muted">آدرس و شماره تماس این صفحه نمونه هستند.</p>
                        <a href="https://www.openstreetmap.org/#map=13/35.72/51.41" target="_blank" rel="noopener noreferrer" class="mt-4 flex min-h-11 items-center justify-center gap-2 rounded-xl border border-border text-xs font-semibold hover:bg-soft"><x-icon name="pin" class="size-4"/> مشاهده نقشه تهران <x-icon name="arrow-left" class="size-4"/></a>
                        <div class="mt-5 flex items-center justify-between border-t border-border pt-4 text-xs"><span class="flex items-center gap-2 text-muted"><x-icon name="phone" class="size-4"/> تلفن نمونه</span><bdi dir="ltr" class="font-semibold">۰۲۱ ۰۰۰۰ ۰۰۰۰</bdi></div>
                        <details class="mt-4 border-t border-border pt-4"><summary class="flex min-h-10 cursor-pointer list-none items-center gap-2 text-xs font-semibold"><x-icon name="clock" class="size-4 text-positive"/> ساعت کاری <bdi class="mr-auto font-normal text-secondary">{{ $business['hours'] }}</bdi><x-icon name="chevron-down" class="size-4"/></summary><dl class="mt-3 space-y-3 border-t border-border pt-4 text-xs text-secondary">@foreach (['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'] as $day)<div class="flex justify-between"><dt>{{ $day }}</dt><dd>{{ $business['hours'] }}</dd></div>@endforeach</dl><p class="mt-4 text-[10px] leading-6 text-muted">ساعت‌های درج‌شده نمایشی هستند و وضعیت زنده را نشان نمی‌دهند.</p></details>
                    </div>
                </section>
                <section class="rounded-2xl border border-border bg-soft p-5"><x-icon name="edit" class="text-pomegranate"/><h2 class="mt-3 text-sm font-bold">تجربه شما، راهنمای نفر بعدی</h2><p class="mt-2 text-xs leading-7 text-secondary">چه چیزی دوست داشتید؟ چه چیزی می‌توانست بهتر باشد؟ چند خط از تجربه‌تان بنویسید.</p><button type="button" @click="openReview()" class="mt-4 min-h-11 text-xs font-bold text-pomegranate">نوشتن اولین خط ←</button></section>
                <div class="flex items-center justify-between gap-2 px-1 text-xs text-muted"><span>صاحب این کسب‌وکار هستید؟</span><a href="{{ route('business.login') }}" class="min-h-10 py-3 font-semibold text-secondary hover:text-pomegranate">ورود کسب‌وکارها ←</a></div>
            </aside>
        </div>

        <section class="mt-14 border-t border-border pt-8"><div class="flex items-center justify-between"><h2 class="text-xl font-bold">شاید اینجاها را هم دوست داشته باشید</h2><a href="{{ route('home') }}#places" class="hidden text-xs text-pomegranate sm:block">کشف بیشتر ←</a></div><div class="mt-6 grid gap-4 sm:grid-cols-2">@foreach ($business['related'] as $related)<a href="{{ route('businesses.show', $related['slug']) }}" class="group flex items-center gap-4 rounded-2xl border border-border bg-surface p-3 hover:border-muted"><img src="{{ $related['image'] }}" alt="{{ $related['name'] }}" loading="lazy" width="112" height="96" class="h-24 w-28 rounded-xl object-cover"><div><h3 class="text-sm font-bold group-hover:text-pomegranate">{{ $related['name'] }}</h3><p class="mt-2 text-xs text-muted">{{ $related['category'] }} · تهران</p><div class="mt-3 flex items-center gap-2"><x-rating :value="4.8"/><span class="text-xs font-semibold">۴٫۸</span></div></div><x-icon name="arrow-left" class="mr-auto hidden text-muted md:block"/></a>@endforeach</div></section>
    </main>
    <footer class="border-t border-border bg-surface"><div class="mx-auto flex max-w-[1240px] flex-col gap-4 px-4 py-7 text-xs text-muted sm:flex-row sm:items-center sm:justify-between md:px-6"><a href="{{ route('home') }}" class="text-2xl font-extrabold text-ink">کیوسک<span class="text-pomegranate">.</span></a><p>اطلاعات، عکس‌ها و نظرهای این صفحه برای نمایش قابلیت‌ها هستند.</p><a href="{{ route('home') }}" class="text-secondary">برگشت به کشف شهر ←</a></div></footer>
    @include('businesses.dialogs')
    <div x-show="toast" x-cloak x-transition.opacity role="status" class="fixed inset-x-4 bottom-5 z-50 mx-auto max-w-md rounded-xl bg-ink px-5 py-4 text-center text-xs leading-6 text-white shadow-soft" x-text="toast"></div>
</div>
</body>
</html>
