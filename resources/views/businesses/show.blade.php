@extends('layouts.community')
@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => $business->name]]" />
@endsection
@section('title', $business->name)
@section('hero')
<section class="business-hero relative isolate overflow-hidden text-white" data-business-hero data-photo-count="{{ $heroPhotos->count() }}" aria-label="تصاویر اصلی کسب‌وکار">
    <div class="business-hero-photos absolute inset-0 grid" aria-hidden="true">
        @foreach($heroPhotos as $photo)
            <img data-media-skeleton class="media-skeleton size-full min-w-0 object-cover" src="{{ route('media.show', $photo) }}" alt="" decoding="async" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
        @endforeach
    </div>
    <div class="business-hero-cover pointer-events-none absolute inset-0"></div>
    <a href="#gallery" data-open-business-gallery class="absolute inset-0 z-10 focus-visible:-outline-offset-4" aria-label="باز کردن گالری تصاویر {{ $business->name }}"><span class="sr-only">باز کردن گالری تصاویر</span></a>
    <div class="pointer-events-none relative z-20 mx-auto flex min-h-[26rem] max-w-6xl flex-col justify-end px-4 pt-16 pb-8 sm:min-h-[30rem] sm:pb-10">
        <p class="mb-3 text-sm font-semibold text-white/85">{{ $category }} · {{ $business->city }}</p>
        <h1 class="max-w-3xl text-4xl font-extrabold leading-tight sm:text-5xl lg:text-6xl">{{ $business->name }}</h1>
        <div class="mt-5 flex flex-wrap items-center gap-3">
            <x-review-stars :rating="$business->reviews_avg_rating ?? 0" />
            <strong class="text-xl">{{ $business->reviews_count ? number_format($business->reviews_avg_rating, 1) : 'بدون امتیاز' }}</strong>
            <a class="pointer-events-auto inline-flex min-h-11 items-center text-sm text-white/90 underline decoration-white/40 underline-offset-4 hover:decoration-white" href="#reviews">{{ $business->reviews_count }} تجربه منتشرشده</a>
            @if($business->price_range)<span class="text-white/50" aria-hidden="true">·</span><span class="text-sm">بازه قیمت: {{ \App\Models\Business::PRICE_RANGES[$business->price_range] }} <bdi dir="ltr">{{ str_repeat('$', $business->price_range) }}</bdi></span>@endif
        </div>
        <div class="mt-4 flex max-w-2xl items-start gap-2 text-sm leading-7 text-white/90"><x-icon name="pin" class="mt-1 size-4" /><p>{{ $business->city }}، {{ $business->address }}</p></div>
        <div class="mt-5 flex flex-wrap items-end justify-between gap-x-8 gap-y-6">
            <div class="flex items-center gap-2 text-sm"><x-icon name="clock" class="size-4" />
                @if($hoursStatus)<p><strong class="{{ $hoursStatus['is_open'] ? 'text-emerald-200' : 'text-rose-200' }}">{{ $hoursStatus['text'] }}</strong><span class="ms-2 text-xs text-white/65">به وقت تهران</span></p>@else<p class="text-white/75">ساعت کاری مشخص نشده</p>@endif
            </div>
            <a href="#gallery" data-open-business-gallery class="pointer-events-auto inline-flex min-h-12 items-center gap-2 rounded-xl border border-white/65 bg-black/15 px-5 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/15"><x-icon name="grid" class="size-4" />مشاهده همه تصاویر ({{ $photos->total() }})</a>
        </div>
    </div>
</section>
@endsection
@section('content')
<div class="grid gap-8 lg:grid-cols-[1fr_320px]">
    <div>
        @if($business->description)<p class="mb-6 whitespace-pre-wrap leading-8 text-secondary">{{ $business->description }}</p>@endif
        <div class="flex flex-wrap gap-3">
            @unless($isOwner)<a class="button-primary" href="{{ route('contribute', ['business' => $business->id]) }}">{{ $myReview ? 'ویرایش تجربه من' : 'نوشتن تجربه من' }}</a>@endunless
            <form method="post" action="{{ route('businesses.save', $business) }}">@csrf @if($saved)@method('delete')@endif<button class="button-secondary">{{ $saved ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره مکان' }}</button></form>
            @unless($isOwner)<a class="button-secondary" href="{{ route('business.claims.create',['business'=>$business->id]) }}">درخواست مالکیت</a>@endunless
        </div>
        <section class="mt-9 scroll-mt-6" id="gallery" aria-labelledby="gallery-title">
            <h2 id="gallery-title" class="mb-4 text-xl font-bold">گالری تصاویر</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @forelse($photos as $photo)<div><a href="#gallery" data-open-business-gallery aria-label="باز کردن عکس {{ $loop->iteration }} در گالری"><img data-media-skeleton class="media-skeleton h-48 w-full rounded-xl object-cover sm:h-64" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="عکس {{ $business->name }}" loading="lazy" width="600" height="400"></a>@if(auth()->id() === $photo->user_id)<form method="post" action="{{ route('media.destroy', $photo) }}">@csrf @method('delete')<button class="button-secondary mt-1">حذف عکس من</button></form>@endif
                @include('contributions.report-form', ['type' => 'media', 'contentId' => $photo->id])</div>
                @empty<p class="text-sm text-muted">هنوز عکسی ثبت نشده است.</p>@endforelse
            </div><div class="mt-4">{{ $photos->withQueryString()->links() }}</div>
        </section>
        <section class="mt-10" id="reviews">
            <h2 class="text-2xl font-bold">تجربه‌های مردم</h2>
            <form class="my-5 flex flex-wrap items-end gap-3" method="get">
                <label>امتیاز<select class="field" name="rating"><option value="">همه امتیازها</option>@for($i=1;$i<=5;$i++)<option value="{{ $i }}" @selected(request('rating') == $i)>{{ $i }} ستاره</option>@endfor</select></label>
                <label>ترتیب<select class="field" name="sort">@foreach(['newest'=>'تازه‌ترین', 'highest'=>'بیشترین امتیاز', 'lowest'=>'کمترین امتیاز', 'helpful'=>'مفیدترین'] as $key=>$label)<option value="{{ $key }}" @selected(request('sort') === $key)>{{ $label }}</option>@endforeach</select></label><button class="button-secondary">نمایش</button>
            </form>
            <div class="space-y-5">
            @forelse($reviews as $review)
                <article class="panel" id="review-{{ $review->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold">{{ $review->author->name }}</h3><x-review-stars :rating="$review->rating" /></div>
                    <p class="mt-2 text-xs text-muted">بازدید {{ \App\Support\PersianDate::format($review->visit_date) }}</p>
                    <p class="my-5 whitespace-pre-wrap leading-8">{{ $review->body }}</p>
                    <div class="flex gap-2">@foreach($review->photos as $photo)<a href="#gallery" data-open-business-gallery aria-label="باز کردن عکس تجربه در گالری"><img data-media-skeleton class="media-skeleton size-20 rounded-lg object-cover" loading="lazy" alt="عکس تجربه" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" width="80" height="80"></a>@endforeach</div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <form method="post" action="{{ route('reviews.helpful', $review) }}">@csrf @if(in_array($review->id, $votes))@method('delete')@endif<button class="button-secondary">{{ in_array($review->id, $votes) ? 'حذف رأی مفید' : 'مفید بود' }} · {{ $review->helpful_count }}</button></form>
                        <a class="button-secondary" href="{{ route('reviews.show', $review) }}">گفت‌وگو</a>
                        @if(auth()->id() === $review->user_id)<a class="button-secondary" href="{{ route('reviews.edit', $review) }}">ویرایش تجربه من</a><form method="post" action="{{ route('reviews.destroy', $review) }}">@csrf @method('delete')<button class="button-secondary">حذف تجربه</button></form>@endif
                    </div>
                    @include('contributions.report-form', ['type' => 'review', 'contentId' => $review->id])
                    @if(isset($ownerReplies[$review->id]))<div class="mt-5 rounded-xl bg-soft p-4"><p class="mb-2 font-bold">پاسخ مالک تأییدشده</p><p class="whitespace-pre-wrap leading-7">{{ $ownerReplies[$review->id]->body }}</p>@include('contributions.report-form', ['type' => 'owner_reply', 'contentId' => $ownerReplies[$review->id]->id])</div>@endif
                    @if($isOwner)<details class="mt-4"><summary class="cursor-pointer py-2">مدیریت پاسخ مالک</summary><form method="post" action="{{ route('reviews.owner-reply', $review) }}">@csrf @method('put')<label>پاسخ شما<textarea class="field" name="body" required minlength="2" maxlength="2000">{{ $ownerReplies[$review->id]->body ?? '' }}</textarea></label><button class="button-primary mt-3">ذخیره پاسخ</button></form><form class="mt-2" method="post" action="{{ route('reviews.owner-reply', $review) }}">@csrf @method('delete')<button class="button-secondary">حذف پاسخ</button></form></details>@endif
                </article>
            @empty<p class="panel text-secondary">هنوز تجربه‌ای با این فیلتر ثبت نشده است.</p>@endforelse
            </div><div class="mt-5">{{ $reviews->links() }}</div>
        </section>
    </div>
    <aside><section class="panel lg:sticky lg:top-5"><h2 class="mb-5 text-xl font-bold">آدرس و اطلاعات تماس</h2><p class="leading-8">{{ $business->city }}، {{ $business->address }}</p>@foreach($business->phones ?? ($business->phone ? [['label'=>'اصلی','value'=>$business->phone]] : []) as $phone)<a class="mt-4 flex justify-between gap-3" href="tel:{{ preg_replace('/[^+0-9]/','',$phone['value']) }}"><span>{{ $phone['label'] }}</span><bdi dir="ltr">{{ $phone['value'] }}</bdi></a>@endforeach @foreach($business->websites ?? ($business->website ? [['label'=>'وب‌سایت','url'=>$business->website]] : []) as $website)<a class="mt-4 block break-all text-pomegranate" href="{{ $website['url'] }}" rel="nofollow noopener" target="_blank">{{ $website['label'] }} ↗</a>@endforeach @if($business->weekly_hours)<h3 class="mt-6 font-bold">ساعت کار به وقت تهران</h3>@if($hoursStatus)<p class="mt-2 font-bold {{ $hoursStatus['is_open'] ? 'text-positive' : 'text-pomegranate' }}">{{ $hoursStatus['text'] }}</p>@endif<details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">برنامه کامل هفته</summary><dl class="mt-3 space-y-2 text-sm">@foreach(\App\Services\BusinessHours::DAYS as $day)<div class="flex justify-between gap-3"><dt>{{ \App\Services\BusinessHours::LABELS[$day] }}</dt><dd class="text-left">@if($business->weekly_hours[$day]['closed'])تعطیل@else @foreach($business->weekly_hours[$day]['shifts'] as $shift)<span class="block" dir="ltr">{{ $shift['opens'] }}–{{ $shift['closes'] }}{{ $shift['next_day'] ? ' +1' : '' }}</span>@endforeach @endif</dd></div>@endforeach</dl></details>@elseif($business->opening_hours)<h3 class="mt-6 font-bold">ساعت کار به وقت تهران</h3><p class="mt-2 whitespace-pre-wrap text-sm leading-7">{{ $business->opening_hours }}</p>@endif</section></aside>
</div>
<dialog data-business-gallery data-gallery-url="{{ route('businesses.show', $business->slug) }}" class="business-gallery-dialog" aria-labelledby="business-gallery-title">
    <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3 sm:px-6">
        <div><h2 id="business-gallery-title" class="font-bold">گالری {{ $business->name }}</h2><p data-gallery-count class="mt-1 text-xs text-white/60"></p></div>
        <button type="button" data-gallery-close class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm hover:bg-white/10">بستن<x-icon name="close" class="size-5" /></button>
    </div>
    <div class="business-gallery-body">
        <div class="business-gallery-stage relative flex min-h-0 items-center justify-center bg-black/30">
            <img data-gallery-image alt="" class="media-skeleton max-h-full max-w-full object-contain" hidden>
            <p data-gallery-empty class="px-6 text-center text-sm text-white/70" hidden>هنوز عکسی برای این کسب‌وکار منتشر نشده است.</p>
            <div data-gallery-navigation class="absolute inset-x-3 bottom-3 flex items-center justify-between" hidden>
                <button type="button" data-gallery-prev class="gallery-arrow" aria-label="عکس قبلی"><x-icon name="chevron-right" /></button>
                <span data-gallery-position class="rounded-full bg-black/60 px-3 py-1 text-xs"></span>
                <button type="button" data-gallery-next class="gallery-arrow" aria-label="عکس بعدی"><x-icon name="chevron-left" /></button>
            </div>
        </div>
        <div class="business-gallery-sidebar min-h-0 overflow-y-auto p-4">
            <div data-gallery-thumbnails class="grid grid-cols-3 gap-2 lg:grid-cols-2"></div>
            <p data-gallery-status role="status" aria-live="polite" class="mt-4 text-center text-sm text-white/70"></p>
            <button type="button" data-gallery-more class="mt-4 min-h-11 w-full rounded-lg border border-white/25 px-4 text-sm hover:bg-white/10 disabled:opacity-50" hidden>تصاویر بیشتر</button>
        </div>
    </div>
</dialog>
@endsection
