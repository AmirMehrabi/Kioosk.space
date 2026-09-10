@extends('layouts.community')

@section('title', $portal === \App\Enums\Portal::Business ? 'مدیریت کسب‌وکارها' : 'حساب کاربری')

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => $portal === \App\Enums\Portal::Business ? 'مدیریت کسب‌وکارها' : 'حساب کاربری']]" />
@endsection

@section('content')
    @if ($portal === \App\Enums\Portal::Business)
        <section class="mx-auto max-w-5xl" aria-labelledby="business-dashboard-title">
            <div class="mb-8 max-w-2xl sm:mb-10">
                <p class="mb-2 text-sm font-bold text-pomegranate">برای صاحبان کسب‌وکار</p>
                <h1 id="business-dashboard-title" class="text-3xl font-extrabold tracking-tight sm:text-4xl">مدیریت کسب‌وکارها</h1>
                <p class="mt-3 text-sm leading-8 text-secondary sm:text-base">کسب‌وکارهای متصل به حساب شما اینجا هستند. برای ویرایش اطلاعات هر کسب‌وکار، آن را انتخاب کنید.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18.75rem]">
                <section aria-labelledby="connected-businesses-title">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 id="connected-businesses-title" class="text-sm font-bold text-secondary">کسب‌وکارهای متصل</h2>
                        @if ($businesses->isNotEmpty())
                            <span class="text-sm text-muted">{{ $businesses->count() }} کسب‌وکار</span>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-border bg-surface">
                        @forelse ($businesses as $business)
                            <div class="flex flex-col gap-4 border-b border-border p-5 last:border-b-0 sm:flex-row sm:items-center">
                                <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-soft text-secondary" aria-hidden="true"><x-icon name="store" /></span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold">{{ $business->name }}</h3>
                                    <p class="mt-1 text-sm text-muted">مالکیت این کسب‌وکار تأیید شده است</p>
                                    <p class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-positive"><x-icon name="check" class="size-4" /> فعال</p>
                                </div>
                                <a href="{{ route('business.businesses.edit', $business) }}" class="button-primary shrink-0">مدیریت اطلاعات <x-icon name="arrow-left" class="size-4" /></a>
                            </div>
                        @empty
                            <div class="p-6 sm:p-8">
                                <span class="flex size-12 items-center justify-center rounded-2xl bg-soft text-secondary" aria-hidden="true"><x-icon name="store" /></span>
                                <h3 class="mt-4 text-lg font-bold">هنوز کسب‌وکاری به حساب شما متصل نیست</h3>
                                <p class="mt-2 max-w-xl text-sm leading-7 text-secondary">پس از تأیید مالکیت، کسب‌وکارتان اینجا ظاهر می‌شود و می‌توانید اطلاعات آن را مدیریت کنید.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <aside aria-labelledby="business-help-title">
                    <h2 id="business-help-title" class="mb-3 text-sm font-bold text-secondary">کسب‌وکارتان اینجا نیست؟</h2>
                    <div class="rounded-2xl border border-border bg-surface p-5">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-pomegranate/10 text-pomegranate" aria-hidden="true"><x-icon name="badge" /></span>
                        <p class="mt-4 text-sm leading-7 text-secondary">اگر مالک یک کسب‌وکار هستید، درخواست مالکیت بفرستید تا پس از بررسی بتوانید آن را مدیریت کنید.</p>
                        <a class="button-secondary mt-5 w-full" href="{{ route('business.claims.create') }}">درخواست مالکیت</a>
                        <a class="mt-2 flex min-h-11 w-full items-center justify-center text-sm font-semibold text-secondary hover:text-pomegranate" href="{{ route('business.claims.index') }}">پیگیری درخواست‌ها</a>
                    </div>
                    <a href="{{ route('contributions.index') }}" class="mt-4 flex min-h-11 items-center gap-2 rounded-xl px-2 text-sm font-semibold text-secondary hover:bg-soft hover:text-pomegranate"><x-icon name="edit" class="size-4" /> مشارکت‌های من</a>
                </aside>
            </div>
        </section>
    @else
        <section class="mx-auto max-w-5xl" aria-labelledby="account-title">
            <div class="mb-8 max-w-2xl sm:mb-10">
                <p class="mb-2 text-sm font-bold text-pomegranate">حساب کاربری</p>
                <h1 id="account-title" class="text-3xl font-extrabold tracking-tight sm:text-4xl">فضای شخصی شما در کیوسک</h1>
                <p class="mt-3 text-sm leading-8 text-secondary sm:text-base">اطلاعات حساب، مشارکت‌ها و دسترسی به ابزارهای کسب‌وکار را از اینجا پیدا می‌کنید.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18.75rem]">
                <section aria-labelledby="account-actions-title">
                    <h2 id="account-actions-title" class="mb-3 text-sm font-bold text-secondary">کارهایی که می‌توانید انجام دهید</h2>
                    <div class="overflow-hidden rounded-2xl border border-border bg-surface">
                        <a href="{{ route('contributions.index') }}" class="flex min-h-23 items-center gap-4 border-b border-border px-5 py-4 transition-colors hover:bg-soft">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-pomegranate/10 text-pomegranate" aria-hidden="true"><x-icon name="edit" /></span>
                            <span class="min-w-0 flex-1"><strong class="block text-base">مشارکت‌های من</strong><small class="mt-1 block text-sm text-muted">تجربه‌ها، مکان‌ها و گزارش‌هایی که فرستاده‌اید</small></span>
                            <x-icon name="chevron-left" class="size-5 shrink-0 text-muted" />
                        </a>
                        <a href="{{ route('home') }}#places" class="flex min-h-23 items-center gap-4 border-b border-border px-5 py-4 transition-colors hover:bg-soft">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-soft text-secondary" aria-hidden="true"><x-icon name="map" /></span>
                            <span class="min-w-0 flex-1"><strong class="block text-base">کشف مکان‌های شهر</strong><small class="mt-1 block text-sm text-muted">جست‌وجو و دیدن تجربه دیگران درباره مکان‌ها</small></span>
                            <x-icon name="chevron-left" class="size-5 shrink-0 text-muted" />
                        </a>
                        <a href="{{ route('business.dashboard') }}" class="flex min-h-23 items-center gap-4 px-5 py-4 transition-colors hover:bg-soft">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-soft text-secondary" aria-hidden="true"><x-icon name="store" /></span>
                            <span class="min-w-0 flex-1"><strong class="block text-base">بخش کسب‌وکارها</strong><small class="mt-1 block text-sm text-muted">درخواست مالکیت یا مدیریت کسب‌وکارهای متصل</small></span>
                            <x-icon name="chevron-left" class="size-5 shrink-0 text-muted" />
                        </a>
                    </div>
                </section>

                <aside aria-labelledby="account-profile-title">
                    <h2 id="account-profile-title" class="mb-3 text-sm font-bold text-secondary">مشخصات حساب</h2>
                    <div class="rounded-2xl border border-border bg-surface p-5">
                        <div class="flex items-center gap-3">
                            <span class="flex size-12 items-center justify-center rounded-full bg-pomegranate/10 text-lg font-extrabold text-pomegranate" aria-hidden="true">{{ mb_substr(trim(auth()->user()->name) ?: 'ک', 0, 1) }}</span>
                            <div class="min-w-0"><p class="truncate font-bold">{{ auth()->user()->name }}</p><p class="mt-1 text-sm text-muted">عضو کیوسک</p></div>
                        </div>
                        <dl class="mt-5 border-t border-border pt-4">
                            <div class="flex items-center justify-between gap-4"><dt class="text-sm text-muted">شماره همراه</dt><dd dir="ltr" class="text-sm font-semibold">{{ auth()->user()->mobile ? \App\Support\IranianMobile::display(auth()->user()->mobile) : '—' }}</dd></div>
                            <div class="mt-3 flex items-center gap-1.5 text-sm font-semibold text-positive"><x-icon name="check" class="size-4" /> تأیید شده</div>
                        </dl>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="min-h-11 rounded-xl px-2 text-sm font-semibold text-secondary hover:bg-soft hover:text-pomegranate">خروج از حساب</button>
                    </form>
                </aside>
            </div>
        </section>
    @endif
@endsection
