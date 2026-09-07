@extends('layouts.community')
@section('title', 'تماس با ما')
@section('content')
<section class="space-y-8">
    <div class="grid gap-5 lg:grid-cols-1">
        <div class="space-y-5">
            <div class="panel relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-pomegranate/10 via-transparent to-soft pointer-events-none"></div>
                <div class="relative">
                    <p class="mb-3 font-bold text-pomegranate">ارتباط با کیوسک</p>
                    <h1 class="text-4xl font-extrabold sm:text-5xl">تماس با ما</h1>
                    <p class="mt-5 max-w-2xl leading-8 text-secondary">برای پرسش‌های فنی، پشتیبانی و همکاری می‌توانید از راه‌های زیر با ما در ارتباط باشید یا پیام خود را از همین صفحه ارسال کنید.</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <article class="panel">
                    <div class="flex items-start gap-3">
                        <span class="rounded-xl bg-pomegranate/10 p-3 text-pomegranate"><x-icon name="message" class="size-6" /></span>
                        <div>
                            <h2 class="font-bold">ایمیل</h2>
                            <p class="mt-2 text-sm leading-7 text-secondary">برای سوالات فنی و پشتیبانی</p>
                            <a class="mt-3 block break-all font-semibold text-pomegranate" href="mailto:{{ $contactEmail }}" dir="ltr">{{ $contactEmail }}</a>
                        </div>
                    </div>
                </article>
                <article class="panel">
                    <div class="flex items-start gap-3">
                        <span class="rounded-xl bg-pomegranate/10 p-3 text-pomegranate"><x-icon name="phone" class="size-6" /></span>
                        <div>
                            <h2 class="font-bold">تلفن</h2>
                            <p class="mt-2 text-sm leading-7 text-secondary">برای پشتیبانی سریع و مشاوره</p>
                            <a class="mt-3 block font-semibold text-pomegranate" href="tel:+983491097953" dir="ltr">{{ $contactPhone }}</a>
                        </div>
                    </div>
                </article>
                <article class="panel sm:col-span-3 lg:col-span-1">
                    <div class="flex items-start gap-3">
                        <span class="rounded-xl bg-pomegranate/10 p-3 text-pomegranate"><x-icon name="pin" class="size-6" /></span>
                        <div>
                            <h2 class="font-bold">آدرس دفتر</h2>
                            <p class="mt-2 text-sm leading-7 text-secondary">{{ $contactAddress }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
        <div class="panel space-y-4">
            <div class="flex items-center gap-3">
                <span class="rounded-xl bg-pomegranate/10 p-3 text-pomegranate"><x-icon name="info" class="size-6" /></span>
                <div>
                    <h2 class="text-xl font-bold">موقعیت دفتر</h2>
                    <p class="text-sm text-secondary">نمایی از محل استقرار تیم ما در کرمان</p>
                </div>
            </div>
            <div class="overflow-hidden rounded-2xl border border-border bg-soft">
                <iframe
                    title="نقشه دفتر کیوسک"
                    src="{{ $mapUrl }}"
                    class="h-[320px] w-full"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="panel space-y-4">
            <div class="flex items-center gap-3">
                <span class="rounded-xl bg-pomegranate/10 p-3 text-pomegranate"><x-icon name="message" class="size-6" /></span>
                <div>
                    <h2 class="text-2xl font-bold">ارسال پیام</h2>
                    <p class="text-sm text-secondary">پیامتان را بنویسید تا تیم پشتیبانی بررسی کند.</p>
                </div>
            </div>
            <form action="{{ route('contact.store') }}" method="post" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">نام و نام خانوادگی<input class="field" name="name" value="{{ old('name') }}" autocomplete="name" required><span class="field-error">@error('name'){{ $message }}@enderror</span></label>
                    <label class="block">ایمیل<input class="field" name="email" type="email" value="{{ old('email') }}" autocomplete="email" dir="ltr" required><span class="field-error">@error('email'){{ $message }}@enderror</span></label>
                </div>
                <label class="block">موضوع<input class="field" name="subject" value="{{ old('subject') }}" maxlength="160"><span class="field-error">@error('subject'){{ $message }}@enderror</span></label>
                <label class="block">پیام<textarea class="field" name="message" rows="7" minlength="20" maxlength="4000" required>{{ old('message') }}</textarea><span class="field-error">@error('message'){{ $message }}@enderror</span></label>
                <div class="flex flex-wrap items-center gap-3">
                    <button class="button-primary">ارسال پیام</button>
                    <p class="text-sm text-muted">پاسخ‌ها از طریق همین ایمیل ارسال می‌شود.</p>
                </div>
            </form>
        </section>
        <aside class="panel space-y-4">
            <h2 class="text-2xl font-bold">راه‌های سریع ارتباط</h2>
            <div class="space-y-3 text-sm leading-7 text-secondary">
                <p>• ایمیل: {{ $contactEmail }}</p>
                <p>• تلفن: {{ $contactPhone }}</p>
                <p>• آدرس: {{ $contactAddress }}</p>
            </div>
            <div class="rounded-2xl bg-soft p-4 text-sm leading-7 text-secondary">
                <p class="font-bold text-ink">ساعات پاسخ‌گویی</p>
                <p class="mt-2">در روزهای کاری، پیام‌های دریافتی در کوتاه‌ترین زمان بررسی می‌شوند.</p>
            </div>
        </aside>
    </div>
</section>
@endsection
