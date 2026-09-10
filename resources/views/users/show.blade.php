@extends('layouts.community')
@section('title', $user->name)
@section('metaDescription', 'پروفایل '.$user->name.' و تجربه‌های منتشرشده او درباره کسب‌وکارهای محلی در کیوسک.')
@section('canonical', route('users.show', $user->slug))
@push('structured-data')<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>@endpush
@section('breadcrumbs')<x-breadcrumbs :items="[['label' => $user->name]]" />@endsection
@section('content')
<header class="panel mb-8"><h1 class="text-3xl font-extrabold">{{ $user->name }}</h1><dl class="mt-5 flex flex-wrap gap-6 text-sm"><div><dt class="text-muted">تجربه منتشرشده</dt><dd class="mt-1 text-xl font-bold">{{ $reviewCount }}</dd></div><div><dt class="text-muted">رأی مفید</dt><dd class="mt-1 text-xl font-bold">{{ $helpfulVotes }}</dd></div></dl></header>
<section aria-labelledby="recent-reviews"><h2 id="recent-reviews" class="mb-5 text-2xl font-bold">تجربه‌های اخیر</h2><div class="grid gap-4">
@forelse($reviews as $review)<article class="panel"><div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-lg font-bold"><a class="hover:text-pomegranate" href="{{ route('businesses.show', $review->business->slug) }}">{{ $review->business->name }}</a></h3><span class="font-bold text-pomegranate">{{ $review->rating }} از ۵</span></div><p class="mt-4 whitespace-pre-wrap leading-8 text-secondary">{{ $review->body }}</p><a class="mt-3 inline-flex text-sm font-semibold text-pomegranate" href="{{ route('reviews.show', $review) }}">مشاهده تجربه و گفت‌وگو</a></article>
@empty<p class="panel text-secondary">هنوز تجربه منتشرشده‌ای وجود ندارد.</p>@endforelse
</div><div class="mt-6">{{ $reviews->links() }}</div></section>
@endsection
