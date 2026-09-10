<div class="grid gap-4 sm:grid-cols-2">
    @forelse($businesses as $business)
        <article class="panel flex flex-col gap-3">
            <p class="text-sm text-muted">{{ $business->category?->name }}{{ $business->location ? ' · '.$business->location->name : '' }}</p>
            <h2 class="text-xl font-bold"><a class="hover:text-pomegranate" href="{{ route('businesses.show', $business->slug) }}">{{ $business->name }}</a></h2>
            <p class="text-sm">@if($business->reviews_count)امتیاز {{ number_format($business->reviews_avg_rating, 1) }} از ۵ · {{ $business->reviews_count }} نظر@else هنوز نظری ثبت نشده است @endif</p>
            <p class="text-sm leading-7 text-secondary">{{ $business->address }}</p>
        </article>
    @empty
        <p class="panel text-secondary">هنوز مکان تأییدشده‌ای در این صفحه وجود ندارد.</p>
    @endforelse
</div>
<div class="mt-6">{{ $businesses->links() }}</div>
