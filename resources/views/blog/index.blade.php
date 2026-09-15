@extends('layouts.community')
@section('title', 'وبلاگ')
@section('metaDescription', 'یادداشت‌های کیوسک دربارهٔ تجربه‌های واقعی، کسب‌وکارهای محلی و ساختن یک راهنمای شهری مفید.')

@section('content')
<div class="mx-auto max-w-4xl py-4 sm:py-10">
    <header class="grid gap-8 border-b border-ink pb-10 sm:grid-cols-[1fr_auto] sm:items-end sm:pb-14">
        <div class="flex flex-col gap-5">
            <p class="text-sm font-bold text-pomegranate">یادداشت‌های کیوسک</p>
            <h1 class="max-w-2xl text-4xl font-extrabold leading-[1.5] tracking-tight sm:text-6xl">دربارهٔ شهر، آدم‌ها و چیزهایی که ارزش پیدا کردن دارند.</h1>
        </div>
        <span class="flex size-20 items-center justify-center rounded-full bg-pomegranate text-4xl font-extrabold text-white sm:size-28" aria-hidden="true">ک</span>
    </header>

    <section class="py-10 sm:py-14" aria-labelledby="latest-posts">
        <div class="mb-7 flex items-center justify-between gap-4">
            <h2 id="latest-posts" class="text-sm font-extrabold">تازه‌ترین نوشته</h2>
            <span class="h-px flex-1 bg-border" aria-hidden="true"></span>
        </div>

        @foreach($posts as $post)
            <article class="group grid gap-6 border-b border-border py-7 sm:grid-cols-[10rem_1fr] sm:gap-10 sm:py-10">
                <div class="flex flex-row gap-3 text-xs font-semibold text-muted sm:flex-col">
                    <time>{{ $post['published_at'] }}</time>
                    <span class="hidden sm:inline" aria-hidden="true">—</span>
                    <span>{{ $post['reading_time'] }} مطالعه</span>
                </div>
                <div class="flex flex-col items-start gap-4">
                    <p class="text-xs font-bold text-pomegranate">{{ $post['eyebrow'] }}</p>
                    <h3 class="text-2xl font-extrabold leading-[1.65] tracking-tight sm:text-4xl">
                        <a class="transition-colors group-hover:text-pomegranate" href="{{ route('blog.show', $post['slug']) }}">{{ $post['title'] }}</a>
                    </h3>
                    <p class="max-w-2xl text-base leading-8 text-secondary sm:text-lg sm:leading-9">{{ $post['excerpt'] }}</p>
                    <a class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-extrabold text-ink group-hover:text-pomegranate" href="{{ route('blog.show', $post['slug']) }}">ادامهٔ نوشته <x-icon name="arrow-left" class="size-4" /></a>
                </div>
            </article>
        @endforeach
    </section>

    <aside class="grid gap-5 rounded-3xl bg-ink p-7 text-white sm:grid-cols-[1fr_auto] sm:items-center sm:p-10">
        <div class="flex flex-col gap-2">
            <h2 class="text-xl font-extrabold sm:text-2xl">ما هنوز اول راهیم.</h2>
            <p class="text-sm leading-7 text-white/70">اگر جای خوبی می‌شناسید، کیوسک با تجربهٔ شما مفیدتر می‌شود.</p>
        </div>
        <a href="{{ route('contribute') }}" class="inline-flex min-h-11 w-fit items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-ink hover:bg-soft">نوشتن تجربه <x-icon name="arrow-left" class="size-4" /></a>
    </aside>
</div>
@endsection
