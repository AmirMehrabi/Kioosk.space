@extends('layouts.community')
@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => $business->name]]" />
@endsection
@section('title', $business->name)
@section('content')
<div class="grid gap-8 lg:grid-cols-[1fr_320px]">
    <div>
        <p class="mb-3 text-sm text-muted">{{ $category }} · {{ $business->city }}</p>
        <h1 class="text-3xl font-extrabold sm:text-4xl">{{ $business->name }}</h1>
        @if($business->description)<p class="mt-4 whitespace-pre-wrap leading-8 text-secondary">{{ $business->description }}</p>@endif
        <x-review-stars class="mt-5" :rating="$business->reviews_avg_rating ?? 0" /><p class="my-5 text-xl text-pomegranate">★ {{ $business->reviews_count ? number_format($business->reviews_avg_rating, 1) : 'بدون امتیاز' }} <span class="text-sm text-muted">از {{ $business->reviews_count }} تجربه منتشرشده</span></p>
        <div class="flex flex-wrap gap-3">
            @unless($isOwner)<a class="button-primary" href="{{ route('contribute', ['business' => $business->id]) }}">{{ $myReview ? 'ویرایش تجربه من' : 'نوشتن تجربه من' }}</a>@endunless
            <form method="post" action="{{ route('businesses.save', $business) }}">@csrf @if($saved)@method('delete')@endif<button class="button-secondary">{{ $saved ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره مکان' }}</button></form>
            @unless($isOwner)<a class="button-secondary" href="{{ route('business.claims.create',['business'=>$business->id]) }}">درخواست مالکیت</a>@endunless
        </div>
        @if($featuredPhotos->isNotEmpty())<section class="mt-8" aria-label="تصاویر اصلی کسب‌وکار"><div @class(['grid gap-2 overflow-hidden rounded-2xl','grid-cols-2 sm:grid-cols-4 sm:grid-rows-2'=>$featuredPhotos->count()>1,'h-80 sm:h-[28rem]'=>$featuredPhotos->count()>1])>@foreach($featuredPhotos as $photo)<a href="{{ route('media.show',$photo) }}" @class(['relative overflow-hidden','sm:col-span-2 sm:row-span-2'=>$loop->first && $featuredPhotos->count()>2])><img class="h-full min-h-40 w-full object-cover" src="{{ route('media.show',[$photo,'thumbnail'=>1]) }}" alt="تصویر اصلی {{ $business->name }}"></a>@endforeach</div></section>@endif
        <section class="mt-9" aria-labelledby="gallery-title">
            <h2 id="gallery-title" class="mb-4 text-xl font-bold">گالری تصاویر</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @forelse($photos as $photo)<div><a href="{{ route('media.show', $photo) }}" target="_blank" rel="noopener"><img class="h-48 w-full rounded-xl object-cover sm:h-64" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="عکس {{ $business->name }}" loading="lazy"></a>@if(auth()->id() === $photo->user_id)<form method="post" action="{{ route('media.destroy', $photo) }}">@csrf @method('delete')<button class="button-secondary mt-1">حذف عکس من</button></form>@endif
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
                    <div class="flex gap-2">@foreach($review->photos as $photo)<a href="{{ route('media.show', $photo) }}"><img class="size-20 rounded-lg object-cover" loading="lazy" alt="عکس تجربه" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}"></a>@endforeach</div>
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
@endsection
