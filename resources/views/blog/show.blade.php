@extends('layouts.community')
@section('metaTitle', $post['title'].' | وبلاگ کیوسک')
@section('title', $post['title'])
@section('metaDescription', $post['excerpt'])
@section('ogType', 'article')

@push('structured-data')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $post['excerpt'],
    'inLanguage' => 'fa-IR',
    'mainEntityOfPage' => route('blog.show', $post['slug']),
    'publisher' => ['@type' => 'Organization', 'name' => 'کیوسک'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl py-4 sm:py-10">
    <a href="{{ route('blog.index') }}" class="mb-10 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-muted hover:text-pomegranate"><x-icon name="arrow-left" class="size-4 rotate-180" />همهٔ نوشته‌ها</a>

    <header class="flex flex-col gap-6 border-b border-ink pb-10 sm:pb-14">
        <p class="text-sm font-bold text-pomegranate">{{ $post['eyebrow'] }}</p>
        <h1 class="text-4xl font-extrabold leading-[1.5] tracking-tight sm:text-6xl">{{ $post['title'] }}</h1>
        <p class="max-w-2xl text-lg leading-9 text-secondary sm:text-xl sm:leading-10">{{ $post['excerpt'] }}</p>
        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-muted">
            <time>{{ $post['published_at'] }}</time><span aria-hidden="true">·</span><span>{{ $post['reading_time'] }} مطالعه</span><span aria-hidden="true">·</span><span>کیوسک</span>
        </div>
    </header>

    <div class="editorial-prose pt-10 sm:pt-14">
        {!! $content !!}
    </div>

    <footer class="mt-16 border-t border-ink pt-8">
        <div class="flex flex-col items-start gap-5 rounded-3xl bg-soft p-6 sm:p-8">
            <p class="text-xs font-extrabold text-pomegranate">حالا نوبت شماست</p>
            <h2 class="text-2xl font-extrabold leading-10">یک تجربهٔ واقعی، از ده نظر کوتاه مفیدتر است.</h2>
            <a href="{{ route('contribute') }}" class="button-primary">شروع مشارکت <x-icon name="arrow-left" class="size-4" /></a>
        </div>
    </footer>
</article>
@endsection
