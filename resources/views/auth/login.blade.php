@extends('layouts.auth')
@section('title', $portal->label())
@section('content')
    <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-soft text-secondary" aria-hidden="true">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 5h4M11 18h2"/></svg>
    </div>
    <h1 class="text-2xl font-bold leading-relaxed sm:text-[28px]">{{ $portal === \App\Enums\Portal::Public ? 'خوش آمدید!' : ($portal === \App\Enums\Portal::Business ? 'کسب‌وکارتان، از اینجا' : 'ورود به بخش مدیریت') }}</h1>
    <p class="mt-2 text-sm leading-7 text-secondary">شماره موبایل خود را وارد کنید؛ یک کد ورود برایتان پیامک می‌کنیم.</p>
    <form action="{{ route($portal->route('otp.send')) }}" method="POST" class="mt-8" data-auth-form>
        @csrf
        <label for="mobile" class="mb-2 block text-sm font-semibold">شماره موبایل</label>
        <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="tel" autocomplete="tel" maxlength="32" required autofocus value="{{ old('mobile') }}" placeholder="۰۹۱۲ ۳۴۵ ۶۷۸۹" aria-describedby="mobile-help{{ $errors->any() ? ' form-errors' : '' }}" aria-invalid="{{ $errors->has('mobile') ? 'true' : 'false' }}" class="h-14 w-full rounded-xl border border-border bg-surface px-4 text-left text-lg tabular-nums outline-none transition focus:border-pomegranate focus:ring-4 focus:ring-pomegranate/10 aria-invalid:border-pomegranate" data-mobile-input>
        <p id="mobile-help" class="mt-2 text-xs leading-6 text-muted">شماره موبایل ایران؛ با ۰۹ یا ‎+۹۸</p>
        <button type="submit" class="mt-6 flex min-h-13 w-full items-center justify-center gap-2 rounded-xl bg-pomegranate px-5 text-sm font-bold text-white transition hover:bg-pomegranate-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-pomegranate disabled:cursor-wait disabled:opacity-60" data-submit-label="در حال ارسال…">دریافت کد ورود <span aria-hidden="true">←</span></button>
    </form>
    <p class="mt-5 text-xs leading-7 text-muted">{{ $portal === \App\Enums\Portal::Admin ? 'این بخش ویژه همکاران مجاز کیوسک است. ثبت‌نام عمومی ندارد.' : 'اگر هنوز حساب ندارید، پس از تأیید شماره برایتان ساخته می‌شود. نیازی به رمز عبور نیست.' }}</p>
@endsection
@section('below')
    @if ($portal !== \App\Enums\Portal::Admin)
        <p class="mt-6 text-center text-sm text-secondary">{{ $portal === \App\Enums\Portal::Public ? 'صاحب کسب‌وکار هستید؟' : 'برای کشف مکان‌ها آمده‌اید؟' }} <a href="{{ route($portal === \App\Enums\Portal::Public ? 'business.login' : 'login') }}" class="inline-block rounded-lg px-1 py-3 font-semibold text-pomegranate hover:text-pomegranate-dark focus-visible:outline-2 focus-visible:outline-pomegranate">{{ $portal === \App\Enums\Portal::Public ? 'ورود کسب‌وکارها' : 'ورود به کیوسک' }} <span aria-hidden="true">←</span></a></p>
    @endif
@endsection
