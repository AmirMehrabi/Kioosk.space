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
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-5" aria-label="فهرست اصلی">
        <a href="{{ route('home') }}" class="text-3xl font-extrabold">کیوسک<span class="text-pomegranate">.</span></a>
        <a href="{{ route(auth()->check() ? 'contributions.index' : 'login') }}" class="text-sm">{{ auth()->check() ? 'مشارکت‌های من' : 'ورود / ثبت‌نام' }}</a>
        <a href="{{ route('contribute') }}" class="button-primary w-full text-center sm:w-auto">افزودن مکان یا نوشتن نظر</a>
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
