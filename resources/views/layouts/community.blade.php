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
<x-navbar />
<main id="main">
    @yield('hero')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:py-12">
    @yield('breadcrumbs')
    @if(session('status'))<p role="status" class="mb-5 rounded-xl bg-positive/10 p-4 text-positive">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl bg-pomegranate/10 p-4 text-pomegranate">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @yield('content')
    </div>
</main>
<footer class="mx-auto flex max-w-6xl flex-col gap-4 border-t border-border px-4 py-6 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
    <span>کیوسک؛ شهر از نگاه شما</span>
    <nav class="flex flex-wrap items-center gap-3">
        <a class="nav-link" href="{{ route('discovery') }}">کشف مکان‌ها</a>
        <a class="nav-link" href="{{ route('contact') }}">تماس با ما</a>
    </nav>
</footer>
</body>
</html>
