@extends('layouts.admin')
@section('title', 'مدیریت کسب‌وکارها')
@section('admin-content')
<div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-3xl font-extrabold">مدیریت کسب‌وکارها</h1><p class="mt-2 text-secondary">اطلاعات مکان‌های منتشرشده و وضعیت همه رکوردها را مدیریت کنید.</p></div></div>
@if(isset($featuredBusinesses) && $featuredBusinesses->isNotEmpty())
<section class="panel my-6 border-pomegranate/20 bg-pomegranate/[0.04]" aria-labelledby="featured-businesses-title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="featured-businesses-title" class="flex items-center gap-2 text-lg font-extrabold"><x-icon name="sparkles" class="size-5 text-pomegranate" />کسب‌وکارهای ویژه صفحه اصلی <span class="rounded-full bg-pomegranate px-2.5 py-0.5 text-xs font-bold text-white">{{ $featuredBusinesses->count() }}</span></h2>
            <p class="mt-1 text-sm leading-6 text-muted">هر بار باز شدن صفحه اصلی، یکی از این موارد به‌صورت تصادفی نمایش داده می‌شود. برای مدیریت، روی «مدیریت ویژه» بزنید.</p>
        </div>
        <a href="{{ route('admin.businesses.index', ['featured' => '1']) }}" class="button-secondary text-sm">نمایش فقط ویژه‌ها</a>
    </div>
    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($featuredBusinesses as $featured)
            <article class="flex gap-3 rounded-xl border border-border bg-surface p-3">
                @if($featured->heroPhoto ?? $featured->featuredPhotos->first())
                    <img class="size-14 shrink-0 rounded-lg object-cover" src="{{ route('media.show', [$featured->heroPhoto ?? $featured->featuredPhotos->first(), 'thumbnail' => 1]) }}" alt="">
                @else
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-soft text-muted"><x-icon name="image" class="size-6" /></span>
                @endif
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-sm font-bold">{{ $featured->name }}</h3>
                    <p class="truncate text-xs text-muted">{{ $featured->city }}</p>
                    <a href="{{ route('admin.businesses.edit', $featured) }}" class="mt-1 inline-flex text-xs font-bold text-pomegranate hover:underline">مدیریت ویژه →</a>
                </div>
            </article>
        @endforeach
    </div>
</section>
@elseif(isset($featuredBusinesses))
<section class="panel my-6 border-dashed bg-soft/50" aria-labelledby="featured-businesses-title">
    <h2 id="featured-businesses-title" class="flex items-center gap-2 font-bold"><x-icon name="sparkles" class="size-5 text-pomegranate" />کسب‌وکارهای ویژه صفحه اصلی</h2>
    <p class="mt-2 text-sm leading-6 text-muted">هنوز کسب‌وکاری برای صفحه اصلی انتخاب نشده. برای افزودن، وارد صفحه «مدیریت» هر کسب‌وکار شوید و گزینه «نمایش در صفحه اصلی» را فعال کنید.</p>
</section>
@endif
<form method="get" class="panel my-6 grid gap-3 md:grid-cols-5"><label>جست‌وجو<input class="field" name="query" value="{{ request('query') }}"></label><label>وضعیت<select class="field" name="status"><option value="">همه</option>@foreach(['approved'=>'منتشرشده','pending'=>'در انتظار','corrections'=>'اصلاح','incomplete'=>'ناقص','rejected'=>'ردشده','merged'=>'ادغام‌شده'] as $key=>$label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></label><label>شهر<select class="field" name="city"><option value="">همه</option>@foreach($cities as $city)<option @selected(request('city') === $city->name)>{{ $city->name }}</option>@endforeach</select></label><label>دسته‌بندی<select class="field" name="category"><option value="">همه</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select></label><label>ویژه صفحه اصلی<select class="field" name="featured"><option value="all" @selected(request('featured', 'all') === 'all')>همه</option><option value="1" @selected(request('featured') === '1')>فقط ویژه‌ها</option><option value="0" @selected(request('featured') === '0')>غیر ویژه</option></select></label><button class="button-primary md:col-span-5">اعمال فیلتر</button>@if(request()->has('featured') && request('featured') !== 'all')<a href="{{ route('admin.businesses.index', request()->except('featured')) }}" class="text-center text-sm font-semibold text-pomegranate hover:underline md:col-span-5">حذف فیلتر ویژه</a>@endif</form>
<div class="space-y-3">@forelse($businesses as $business)<article class="panel flex flex-wrap items-center gap-4">@if($business->featuredPhotos->first())<img class="size-20 rounded-xl object-cover" src="{{ route('media.show', [$business->featuredPhotos->first(), 'thumbnail'=>1]) }}" alt="">@endif<div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h2 class="font-bold">{{ $business->name }}</h2>@if($business->is_featured)<span class="inline-flex items-center gap-1 rounded-full bg-pomegranate px-2.5 py-0.5 text-xs font-bold text-white"><x-icon name="sparkles" class="size-3.5" />ویژه صفحه اصلی</span>@endif</div><p class="mt-1 text-sm text-muted">{{ $business->city }} · {{ $business->address }}</p><div class="mt-2 flex flex-wrap gap-2"><span class="admin-status inline-flex">{{ $business->status }}</span>@if($business->is_featured && $business->heroPhoto)<span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">عکس هیرو انتخاب شده</span>@elseif($business->is_featured)<span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">بدون عکس هیرو</span>@endif</div></div>@if($business->status === 'approved')<a class="button-primary" href="{{ route('admin.businesses.edit', $business) }}">{{ $business->is_featured ? 'مدیریت ویژه' : 'مدیریت' }}</a>@elseif($business->status === 'merged' && $business->merged_into_id)<a class="button-secondary" href="{{ route('admin.businesses.edit', $business->merged_into_id) }}">مکان اصلی</a>@else<a class="button-secondary" href="{{ route('admin.submissions.show', $business) }}">ادامه بررسی</a>@endif</article>@empty<p class="panel text-muted">کسب‌وکاری با این فیلتر پیدا نشد.</p>@endforelse</div><div class="mt-6">{{ $businesses->links() }}</div>
@endsection
