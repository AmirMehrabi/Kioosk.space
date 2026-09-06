@extends('layouts.community')
@section('title', 'گفت‌وگو')
@section('content')
<div class="mx-auto max-w-2xl">
    <a class="text-pomegranate" href="{{ route('businesses.show', $review->business->slug) }}">{{ $review->business->name }} ←</a>
    <h1 class="my-5 text-2xl font-bold">گفت‌وگو درباره تجربه {{ $review->author->name }}</h1>
    <p class="panel whitespace-pre-wrap leading-8">{{ $review->body }}</p>
    <div class="my-6 space-y-4">
    @forelse($comments as $comment)
        <article class="panel {{ $comment->parent_id ? 'mr-5 border-r-4' : '' }}"><h2 class="font-bold">{{ $comment->author->name }}</h2>@if($comment->parent_id)<p class="mt-2 text-xs text-muted">در پاسخ به دیدگاه شماره {{ $comment->parent_id }}</p>@endif<p class="my-3 whitespace-pre-wrap leading-7">{{ $comment->body }}</p>
            @unless($comment->parent_id)<details><summary class="cursor-pointer py-2 text-sm">پاسخ به این دیدگاه</summary><form method="post" action="{{ route('reviews.comments', $review) }}">@csrf<input type="hidden" name="parent_id" value="{{ $comment->id }}"><label>پاسخ<textarea class="field" name="body" required minlength="2" maxlength="1000"></textarea></label><button class="button-secondary mt-3">ثبت پاسخ</button></form></details>@endunless
            @if(auth()->id() === $comment->user_id)<details><summary class="cursor-pointer py-2 text-sm">ویرایش دیدگاه من</summary><form method="post" action="{{ route('comments.update', $comment) }}">@csrf @method('put')<label>متن دیدگاه<textarea class="field" name="body" required minlength="2" maxlength="1000">{{ $comment->body }}</textarea></label><button class="button-secondary mt-3">ذخیره</button></form></details><form method="post" action="{{ route('comments.update', $comment) }}">@csrf @method('delete')<button class="button-secondary mt-2">حذف دیدگاه</button></form>@endif
            @include('contributions.report-form', ['type'=>'comment','contentId'=>$comment->id])
        </article>
    @empty<p class="text-muted">هنوز دیدگاهی ثبت نشده است.</p>@endforelse
    </div>{{ $comments->links() }}
    <form class="panel mt-6" method="post" action="{{ route('reviews.comments', $review) }}">@csrf<label>دیدگاه شما<textarea class="field" name="body" required minlength="2" maxlength="1000">{{ old('body') }}</textarea></label><button class="button-primary mt-4">ثبت دیدگاه</button></form>
</div>
@endsection
