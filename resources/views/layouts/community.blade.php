<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#faf8f5">
    <title>@yield('title', 'شهر از نگاه شما') | کیوسک</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-canvas font-sans text-ink antialiased">
<a href="#main" class="sr-only focus:not-sr-only">رفتن به محتوای اصلی</a>
<header class="border-b border-border bg-surface">
    @php($hasBusinessPortal = auth()->check() && auth()->user()->ownedBusinesses()->exists())
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4" aria-label="فهرست اصلی">
        <a href="{{ route('home') }}" class="shrink-0 text-3xl font-extrabold">کیوسک<span class="text-pomegranate">.</span></a>
        @guest
            <div class="mr-auto flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm">
                <a class="font-semibold hover:text-pomegranate" href="{{ route('login') }}">ورود</a>
                <span class="text-border">/</span>
                <a class="font-semibold hover:text-pomegranate" href="{{ route('register') }}">ثبت‌نام</a>
                <a href="{{ route('contribute') }}" class="button-primary text-center">افزودن مکان یا نوشتن نظر</a>
            </div>
        @else
            <div class="mr-auto flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm">
                @if(auth()->user()->hasStaffAccess())
                    <a class="font-semibold hover:text-pomegranate" href="{{ route('account') }}">پرتال کاربر</a>
                    <a class="font-semibold hover:text-pomegranate" href="{{ route('contributions.index') }}">مشارکت‌های من</a>
                    @if($hasBusinessPortal)<a class="font-semibold hover:text-pomegranate" href="{{ route('business.dashboard') }}">پرتال کسب‌وکار</a>@endif
                    <a class="font-semibold text-pomegranate hover:text-pomegranate-dark" href="{{ route('admin.dashboard') }}">پرتال مدیریت</a>
                @else
                    <x-profile-menu :user="auth()->user()" />
                    <a class="font-semibold hover:text-pomegranate" href="{{ route('contributions.index') }}">مشارکت‌های من</a>
                    @if($hasBusinessPortal)<a class="font-semibold hover:text-pomegranate" href="{{ route('business.dashboard') }}">پرتال کسب‌وکار</a>@endif
                @endif
                <a href="{{ route('contribute') }}" class="button-primary text-center">افزودن مکان یا نوشتن نظر</a>
            </div>
        @endguest
    </nav>
</header>
<main id="main" class="mx-auto max-w-6xl px-4 py-8 sm:py-12">
    @if(session('status'))<p role="status" class="mb-5 rounded-xl bg-positive/10 p-4 text-positive">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl bg-pomegranate/10 p-4 text-pomegranate">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @yield('content')
</main>
<footer class="mx-auto max-w-6xl border-t border-border px-4 py-8 text-sm text-muted">کیوسک؛ شهر از نگاه شما <a class="float-left" href="{{ route('account') }}">حساب من</a></footer>
</body>
</html>
