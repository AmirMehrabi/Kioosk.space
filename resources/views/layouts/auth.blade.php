<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#faf8f5">
    <title>@yield('title', 'ورود') | کیوسک</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-canvas font-sans text-ink antialiased selection:bg-pomegranate selection:text-white">
    <div class="flex min-h-dvh flex-col">
        <a href="#main" class="sr-only focus:not-sr-only">رفتن به محتوای اصلی</a>
        <x-navbar :minimal="true" :portal="$portal ?? null" />
        <main id="main" class="flex flex-1 flex-col items-center justify-center px-4 pb-12 pt-5 sm:px-6 sm:pb-20">
            <div class="w-full max-w-[460px] rounded-3xl border border-border bg-surface p-6 shadow-soft sm:p-9">
                @if (isset($portal))
                    <p class="mb-7 text-xs font-semibold text-muted">{{ $portal->label() }}</p>
                @endif
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-border bg-soft p-4 text-sm leading-7 text-secondary">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div id="form-errors" role="alert" tabindex="-1" class="mb-6 rounded-xl border border-pomegranate/25 bg-pomegranate/5 p-4 text-sm leading-7 text-pomegranate-dark">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                @yield('content')
            </div>
            @yield('below')
        </main>
        <footer class="px-5 pb-6 text-center text-xs leading-6 text-muted">کیوسک؛ شهر از نگاه شما</footer>
    </div>
</body>
</html>
