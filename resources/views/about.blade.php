@extends('layouts.community')
@section('title', 'دربارهٔ ما')
@section('metaDescription', 'داستان کیوسک؛ پروژه‌ای مستقل برای پیدا کردن کسب‌وکارهای محلی با کمک تجربه‌های واقعی آدم‌ها.')

@section('content')
<article class="mx-auto max-w-4xl py-4 sm:py-10">
    <header class="relative isolate overflow-hidden border-b border-ink pb-10 sm:pb-16">
        <div class="absolute -start-8 top-0 -z-10 size-40 rounded-full bg-pomegranate/8 sm:size-56" aria-hidden="true"></div>
        <div class="flex max-w-3xl flex-col gap-6">
            <p class="text-sm font-bold text-pomegranate">دربارهٔ کیوسک</p>
            <h1 class="text-4xl font-extrabold leading-[1.5] tracking-tight sm:text-6xl">یک پروژهٔ شخصی برای یک سؤال روزمره: «اینجا خوبه؟»</h1>
            <p class="max-w-2xl text-lg leading-9 text-secondary sm:text-xl sm:leading-10">کیوسک قرار نیست همه‌چیز را دربارهٔ همه‌جا بداند. قرار است چیزهای درست و مفیدی را بداند که انتخاب بعدی شما را کمی بهتر می‌کنند.</p>
        </div>
    </header>

    <div class="grid gap-10 pt-10 sm:pt-14 lg:grid-cols-[12rem_1fr] lg:gap-16">
        <aside class="hidden lg:block">
            <p class="sticky top-8 border-t-2 border-pomegranate pt-4 text-sm font-extrabold leading-7">کوچک شروع می‌کنیم.<br>با دقت رشد می‌کنیم.</p>
        </aside>
        <div class="editorial-prose min-w-0">
            {!! $content !!}
        </div>
    </div>

    <section class="mt-16 grid gap-7 border-y border-ink py-9 sm:grid-cols-[1fr_auto] sm:items-center sm:py-12" aria-labelledby="about-contribute-title">
        <div class="flex max-w-xl flex-col gap-3">
            <p class="text-xs font-extrabold text-pomegranate">کیوسک با مشارکت ساخته می‌شود</p>
            <h2 id="about-contribute-title" class="text-2xl font-extrabold leading-10 sm:text-3xl">شهر را بهتر از هر الگوریتمی می‌شناسید.</h2>
            <p class="text-sm leading-7 text-secondary">راهنما را بخوانید و اولین مکان یا تجربهٔ واقعی‌تان را ثبت کنید.</p>
        </div>
        <a href="{{ route('blog.show', 'contribution-guide') }}" class="button-primary">راهنمای مشارکت <x-icon name="arrow-left" class="size-4" /></a>
    </section>
</article>
@endsection
