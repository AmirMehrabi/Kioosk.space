@props(['review'])
<article data-review-id="{{ $review->id }}" class="home-review flex min-w-0 flex-col overflow-hidden rounded-2xl border border-border bg-surface">
    <div class="flex items-center gap-3 px-5 pt-5">
        <span class="review-avatar flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold" data-tone="{{ $review->user_id % 4 }}" aria-hidden="true">{{ mb_substr($review->author->name, 0, 1) }}</span>
        <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $review->author->name }}</p><p class="mt-0.5 text-xs text-muted">تجربه‌اش را به اشتراک گذاشت</p></div>
        <x-icon name="message" class="size-4 text-border" />
    </div>
    <div class="px-5 pt-4">
        <a class="text-lg font-extrabold leading-7 transition-colors hover:text-pomegranate" href="{{ route('businesses.show', $review->business->slug) }}">{{ $review->business->name }}</a>
        <p class="mt-1 flex items-center gap-1 text-xs text-muted"><x-icon name="pin" class="size-3.5" />{{ $review->business->city }}</p>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2"><x-review-stars :rating="$review->rating" class="home-review-stars" /><time class="text-[11px] text-muted" datetime="{{ $review->created_at->toIso8601String() }}">{{ \App\Support\PersianDate::format($review->created_at->copy()->timezone('Asia/Tehran')->toDateString()) }}</time></div>
    </div>
    <p class="review-excerpt mx-5 mt-4 mb-4 whitespace-pre-wrap break-words text-sm leading-7 text-secondary">{{ \Illuminate\Support\Str::limit($review->body, 240) }}</p>
    @if($review->photos->isNotEmpty())
        <div class="review-gallery mx-5 mb-4" data-photo-count="{{ $review->photos->count() }}" aria-label="عکس‌های تجربه">
            @foreach($review->photos as $photo)
                <a href="{{ route('media.show', $photo) }}" data-review-photo data-full-image="{{ route('media.show', $photo) }}" class="group relative min-h-0 min-w-0 overflow-hidden bg-soft" aria-label="مشاهده عکس {{ $loop->iteration }} از تجربه در {{ $review->business->name }}">
                    <img data-media-skeleton class="media-skeleton size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transform-none" src="{{ route('media.show', [$photo, 'thumbnail' => 1]) }}" alt="عکس تجربه در {{ $review->business->name }}" loading="lazy" width="400" height="300">
                </a>
            @endforeach
        </div>
    @else
        <div class="mx-5 mb-4 flex flex-1 items-end" aria-hidden="true"><span class="font-serif text-6xl leading-none text-pomegranate/10">”</span></div>
    @endif
    <a class="mx-5 mt-auto flex min-h-13 items-center justify-between gap-2 border-t border-border/70 text-xs font-semibold text-secondary transition-colors hover:text-pomegranate" href="{{ route('reviews.show', $review) }}"><span>ادامهٔ تجربه و گفت‌وگو</span><x-icon name="arrow-left" class="size-4" /></a>
</article>
