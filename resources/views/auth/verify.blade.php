@extends('layouts.auth')
@section('title', 'تأیید شماره موبایل')
@section('content')
    <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-soft text-secondary" aria-hidden="true">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9H13a8.5 8.5 0 0 1 8 8v.5Z"/><path d="M8 11h8M8 14h5"/></svg>
    </div>
    <h1 class="text-2xl font-bold leading-relaxed sm:text-[28px]">کد ورود را وارد کنید</h1>
    <p class="mt-2 text-sm leading-7 text-secondary">کد ۵ رقمی ارسال‌شده به <bdi dir="ltr" class="font-semibold text-ink">{{ \App\Support\IranianMobile::display($challenge->mobile) }}</bdi> را وارد کنید.</p>
    <a href="{{ route($portal->route('login')) }}" class="mt-1 inline-block min-h-11 rounded-lg py-3 text-xs font-semibold text-pomegranate focus-visible:outline-2 focus-visible:outline-pomegranate">ویرایش شماره موبایل</a>
    <form action="{{ route($portal->route('otp.verify')) }}" method="POST" class="mt-4" data-auth-form>
        @csrf
        <label for="code" class="mb-2 block text-sm font-semibold">کد تأیید</label>
        <input id="code" name="code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code" maxlength="5" required autofocus aria-describedby="code-help{{ $errors->any() ? ' form-errors' : '' }}" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" class="h-16 w-full rounded-xl border border-border bg-surface px-4 text-center text-3xl tracking-[0.5em] text-ink tabular-nums outline-none transition focus:border-pomegranate focus:ring-4 focus:ring-pomegranate/10 aria-invalid:border-pomegranate" data-otp-input>
        <p id="code-help" class="mt-3 text-xs leading-6 text-muted" data-expiry="{{ $challenge->expires_at->timestamp }}">کد تا ۳ دقیقه معتبر است. می‌توانید آن را کپی و جای‌گذاری کنید.</p>
        <button type="submit" class="mt-6 flex min-h-13 w-full items-center justify-center rounded-xl bg-pomegranate px-5 text-sm font-bold text-white transition hover:bg-pomegranate-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-pomegranate disabled:cursor-wait disabled:opacity-60" data-submit-label="در حال تأیید…">تأیید و ورود</button>
    </form>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-border pt-4">
        <p class="text-xs text-muted">کد را دریافت نکردید؟</p>
        <form action="{{ route($portal->route('otp.resend')) }}" method="POST" data-auth-form>
            @csrf
            <button type="submit" data-resend-at="{{ $challenge->created_at->timestamp + config('otp.resend_seconds') }}" data-submit-label="در حال ارسال…" class="min-h-11 rounded-lg px-2 text-xs font-semibold text-pomegranate hover:text-pomegranate-dark focus-visible:outline-2 focus-visible:outline-pomegranate disabled:cursor-not-allowed disabled:text-muted">ارسال دوباره کد</button>
        </form>
    </div>
@endsection
@section('below')
    <p class="mt-6 max-w-sm text-center text-xs leading-7 text-muted">کد ورود فقط برای شماست؛ آن را با هیچ‌کس به اشتراک نگذارید.</p>
@endsection
