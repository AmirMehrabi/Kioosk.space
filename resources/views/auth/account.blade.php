@extends('layouts.auth')
@section('title', $portal === \App\Enums\Portal::Admin ? 'بخش مدیریت' : 'حساب کاربری')
@section('content')
    <a href="{{ route('contributions.index') }}" class="mb-5 flex min-h-11 items-center justify-center rounded-xl bg-pomegranate px-4 text-white">مشارکت‌های من</a>
    @if($portal === \App\Enums\Portal::Admin)<a href="{{ route('admin.submissions') }}" class="mb-5 block text-pomegranate">بررسی مشارکت‌ها و گزارش‌ها</a>@endif
    <span class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-positive/10 text-2xl text-positive" aria-hidden="true">✓</span>
    <h1 class="text-2xl font-bold">{{ $portal === \App\Enums\Portal::Admin ? 'به بخش مدیریت خوش آمدید' : 'خوش آمدید، '.auth()->user()->name }}</h1>
    <p class="mt-3 text-sm leading-7 text-secondary">شماره <bdi dir="ltr">{{ \App\Support\IranianMobile::display(auth()->user()->mobile) }}</bdi> تأیید شده است.</p>
    @if ($portal === \App\Enums\Portal::Business)
        <div class="mt-6 rounded-xl border border-border bg-soft p-4">
            @forelse ($businesses as $business)
                <p class="py-2 text-sm font-semibold">{{ $business->name }}</p>
            @empty
                <h2 class="text-sm font-bold">هنوز کسب‌وکاری به حساب شما متصل نیست</h2>
                <p class="mt-2 text-xs leading-7 text-secondary">برای مدیریت یک کسب‌وکار، مالکیت شما باید تأیید شود. ورود به این بخش به‌تنهایی دسترسی مدیریت ایجاد نمی‌کند.</p>
            @endforelse
        </div>
    @elseif ($portal === \App\Enums\Portal::Admin)
        <p class="mt-6 rounded-xl bg-soft p-4 text-sm">سطح دسترسی: {{ auth()->user()->platform_role === \App\Enums\PlatformRole::Superadmin ? 'مدیر ارشد' : 'مدیر' }}</p>
        <p class="mt-3 text-xs leading-7 text-muted">برای امنیت حساب، تأیید ورود مدیریت پس از ۳۰ دقیقه دوباره لازم می‌شود.</p>
    @else
        <a href="{{ route('home') }}#places" class="mt-6 flex min-h-13 items-center justify-center rounded-xl bg-pomegranate px-5 text-sm font-bold text-white hover:bg-pomegranate-dark">کشف مکان‌های شهر ←</a>
        <a href="{{ route('business.dashboard') }}" class="mt-2 block rounded-xl py-3 text-center text-sm text-secondary hover:text-pomegranate">رفتن به بخش کسب‌وکارها</a>
    @endif
    <form action="{{ route('logout') }}" method="POST" class="mt-6 border-t border-border pt-4">
        @csrf
        <button type="submit" class="min-h-11 rounded-lg px-2 text-sm text-secondary hover:text-pomegranate focus-visible:outline-2 focus-visible:outline-pomegranate">خروج از حساب</button>
    </form>
@endsection
