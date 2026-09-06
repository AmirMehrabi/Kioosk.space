@extends('layouts.community')
@section('title', 'بررسی مشارکت‌ها')
@section('content')
<h1 class="mb-5 text-3xl font-bold">بررسی مشارکت‌ها</h1><a class="button-secondary mb-5" href="{{ route('admin.reports') }}">گزارش‌های محتوا</a>
<div class="space-y-5">@forelse($businesses as $business)<article class="panel"><h2 class="text-xl font-bold">{{ $business->name }}</h2><p class="my-3">{{ $business->city }} · {{ $business->address }} · {{ ['pending'=>'در انتظار بررسی','corrections'=>'نیازمند اصلاح','incomplete'=>'اطلاعات ناقص','rejected'=>'ردشده'][$business->status] ?? $business->status }}</p>
<a class="button-secondary mb-4" href="{{ route('admin.submissions.show', $business) }}">بررسی تجربه‌ها و عکس‌های مکان</a>
<form class="grid gap-4 sm:grid-cols-2" method="post" action="{{ route('admin.submissions.update',$business) }}">@csrf
    <label>نام<input class="field" name="name" value="{{ $business->name }}"></label><label>دسته‌بندی<select class="field" name="category_id"><option value="">انتخاب</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($business->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label>شهر<input class="field" name="city" value="{{ $business->city }}"></label><label>آدرس<input class="field" name="address" value="{{ $business->address }}"></label>
    <label>تلفن<input class="field" name="phone" value="{{ $business->phone }}"></label><label>وب‌سایت<input class="field" name="website" value="{{ $business->website }}"></label><label>ساعت کار<textarea class="field" name="opening_hours">{{ $business->opening_hours }}</textarea></label>
    <div data-merge-search><label>مکان اصلی برای ادغام<input class="field" type="search" data-merge-query placeholder="نام مکان اصلی"></label><button class="button-secondary mt-2" type="button" data-merge-find>پیدا کردن مکان اصلی</button><label class="mt-3 block">انتخاب مکان<select class="field" name="target_id" data-merge-results><option value="">ابتدا مکان اصلی را جست‌وجو کنید</option></select></label><p class="mt-2 text-sm text-muted" data-merge-status role="status"></p></div>
    <label class="sm:col-span-2">دلیل تصمیم (برای مشارکت‌کننده)<textarea class="field" name="reason" required minlength="3" maxlength="1000"></textarea></label>
    <div class="flex flex-wrap gap-2 sm:col-span-2">@foreach(['edit'=>'ذخیره اطلاعات', 'approve'=>'تأیید و انتشار', 'corrections'=>'درخواست اصلاح', 'reject'=>'رد', 'merge'=>'ادغام با مکان تأییدشده'] as $action=>$label)<button class="button-secondary" name="action" value="{{ $action }}">{{ $label }}</button>@endforeach</div>
</form></article>@empty<p class="panel">مشارکتی در صف نیست.</p>@endforelse</div><div class="mt-5">{{ $businesses->links() }}</div>
@endsection
