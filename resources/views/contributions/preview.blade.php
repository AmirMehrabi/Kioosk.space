@extends('layouts.admin')
@section('title', 'بررسی مکان')
@section('admin-content')
<a class="button-secondary mb-6" href="{{ route('admin.submissions') }}">بازگشت به صف بررسی</a>
<h1 class="text-3xl font-bold">{{ $business->name }}</h1><p class="my-4 leading-8">{{ $business->city }} · {{ $business->address }}</p>
<h2 class="my-5 text-xl font-bold">عکس‌های پیشنهادی</h2><div class="grid grid-cols-2 gap-3 sm:grid-cols-3">@forelse($photos as $photo)<a href="{{ route('media.show', $photo) }}" target="_blank" rel="noopener"><img class="h-48 w-full rounded-xl object-cover" src="{{ route('media.show', [$photo, 'thumbnail'=>1]) }}" alt="عکس پیشنهادی مکان"></a>@empty<p>عکسی ندارد.</p>@endforelse</div>{{ $photos->withQueryString()->links() }}
<h2 class="my-5 text-xl font-bold">تجربه‌های پیشنهادی</h2><div class="space-y-4">@forelse($reviews as $review)<article class="panel"><h3 class="mb-3 font-bold">{{ $review->author->name }}</h3><x-review-stars :rating="$review->rating" /><p class="mt-2 text-sm text-muted">بازدید {{ \App\Support\PersianDate::format($review->visit_date) }}{{ $review->trashed() ? ' · حذف‌شده توسط نویسنده' : '' }}</p><p class="mt-4 whitespace-pre-wrap leading-8">{{ $review->body }}</p></article>@empty<p>تجربه‌ای ندارد.</p>@endforelse</div>{{ $reviews->withQueryString()->links() }}
@endsection
