@extends('layouts.community')
@section('breadcrumbs')
    <x-breadcrumbs :items="auth()->check() ? [['label' => 'مشارکت‌های من', 'url' => route('contributions.index')], ['label' => request()->routeIs('reviews.edit') ? 'ویرایش تجربه من' : 'افزودن مکان یا نوشتن نظر']] : [['label' => 'افزودن مکان یا نوشتن نظر']]" />
@endsection
@section('title', isset($initial['edit_review_id']) ? 'ویرایش تجربه' : 'افزودن مکان یا نوشتن تجربه')
@section('content')
<div id="contribution-app" class="mx-auto max-w-2xl" data-user="{{ auth()->id() }}" data-generic="{{ auth()->check() && preg_match('/^(کاربر|User)(\s|$)/u', auth()->user()->name) ? '1' : '0' }}" data-persist-draft="{{ isset($initial['edit_review_id']) ? '0' : '1' }}">
    <script id="contribution-initial" type="application/json">{!! json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <p class="mb-3 text-xs font-semibold text-pomegranate">تجربهٔ تو، راهنمای نفر بعدی</p>
    <h1 class="text-3xl font-extrabold sm:text-4xl">{{ isset($initial['edit_review_id']) ? 'تجربه‌ات را به‌روز کن.' : 'افزودن مکان یا نوشتن تجربه' }}</h1>
    <p class="mt-3 text-sm leading-7 text-secondary">اول مکان را پیدا کن؛ بعد تجربه‌ات را بنویس یا مکان تازه‌ای معرفی کن.</p>
    <div class="my-6 rounded-xl border border-border bg-surface p-4">
        <p id="step-progress" class="mb-3 text-xs font-semibold text-secondary" role="status" aria-live="polite">مرحله ۱ · پیدا کردن مکان</p>
        <ol id="stepper" aria-label="مراحل مشارکت" class="contribution-steps"></ol>
    </div>
    <div id="resume-draft" class="mb-5 rounded-xl border border-border bg-soft p-4 text-sm leading-7" hidden>پیش‌نویس قبلی‌ات آماده است؛ از همین‌جا ادامه بده. <a href="{{ route('contribute', ['new' => 1]) }}" class="font-semibold text-pomegranate underline underline-offset-4">شروع تجربه تازه</a></div>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <p id="draft-status" class="text-xs text-muted" role="status" aria-live="polite"></p>
        <details id="draft-tools" class="text-xs text-muted" hidden><summary class="cursor-pointer py-2">مدیریت پیش‌نویس</summary><button type="button" id="clear-device-drafts" class="button-secondary text-sm" hidden>پاک‌کردن پیش‌نویس‌های این دستگاه</button></details>
    </div>
    <div id="contribution-error" role="alert" tabindex="-1" class="contribution-alert mb-5" hidden><strong id="error-title"></strong><ul id="error-list" class="mt-2 space-y-2"></ul></div>
    <div id="conflict" class="contribution-notice mb-4" hidden><p>تجربه قبلی شما موجود است. این پیش‌نویس محفوظ می‌ماند.</p><a id="edit-existing" class="button-secondary mt-3">ویرایش تجربه من</a></div>
    <div id="draft-conflict" class="contribution-notice mb-4" hidden><p>نسخه این دستگاه را نگه دارید و تغییرات ذخیره‌شده در حساب را بررسی کنید.</p><a id="reload-draft" target="_blank" rel="noopener" class="button-secondary mt-3">بررسی نسخه حساب در پنجره تازه</a></div>
    <form id="contribution-form" novalidate>
        <section data-step="1" class="panel space-y-5">
            <div class="flex items-center gap-3"><span class="rounded-xl bg-pomegranate/10 p-3 text-pomegranate"><x-icon name="pin" /></span><h2 class="text-2xl font-bold" tabindex="-1">کجا رفتی؟</h2></div><p class="text-sm leading-7 text-secondary">نام و شهر را جست‌وجو کن و مکان درست را از نتایج انتخاب کن. برای افزودن مکان تازه هم ابتدا بررسی می‌کنیم که قبلاً ثبت نشده باشد.</p>
            <div class="grid gap-4 sm:grid-cols-[1fr_11rem]">
                <label class="block text-sm font-semibold">نام مکان<input class="field" id="search-name" type="search" maxlength="180" autocomplete="off" placeholder="مثلاً کافه کاستا" aria-controls="search-results" aria-describedby="search-status"></label>
                <x-city-select :cities="$cities" id="search-city" name="search_city" label="شهر" :value="request('city', session('discovery.city', ''))" />
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3"><button type="button" id="search-businesses" class="button-primary gap-3"><x-icon name="search" class="size-4" />پیدا کردن مکان</button><button type="button" id="locate" class="inline-flex min-h-11 items-center gap-2 text-xs text-secondary"><x-icon name="pin" class="size-4" />نزدیک من</button></div>
            <p id="gps-status" class="text-xs text-muted" role="status"></p>
            <p id="search-status" class="contribution-search-status" role="status" aria-live="polite">نام مکان را بنویس تا پیدایش کنیم.</p>
            <div id="search-results" class="space-y-3" aria-busy="false"></div>
            <button type="button" id="search-more" class="button-secondary" hidden>نتایج بیشتر</button>
            <button type="button" id="new-business" class="button-secondary w-full">افزودن مکان جدید</button>
        </section>
        <section data-step="2" class="panel space-y-5" hidden>
            <h2 class="text-xl font-bold" tabindex="-1">این مکان را به کیوسک اضافه کن</h2>
            <p class="text-sm leading-7 text-muted">نام، دسته‌بندی، شهر و آدرس را وارد کن تا دیگران هم این مکان را پیدا کنند. فیلدهای ستاره‌دار ضروری‌اند.</p>
            <label class="block">نام مکان <span class="text-pomegranate">*</span><input class="field" name="name" maxlength="180" placeholder="مثلاً کافه سرو، شعبه ونک" required><span class="field-error" data-error="name"></span></label>
            <label class="block">دسته‌بندی <span class="text-pomegranate">*</span><select class="field" name="category_id" required><option value="">انتخاب کنید</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select><span class="field-error" data-error="category_id"></span></label>
            <x-city-select :cities="$cities" id="city" name="city" label="شهر" required hint="شهر خود را از فهرست انتخاب کنید."><span class="field-error" data-error="city"></span></x-city-select>
            <label class="block">آدرس کوتاه <span class="text-pomegranate">*</span><input class="field" name="address" maxlength="500" placeholder="محله، خیابان، کوچه و پلاک" required><span class="field-error" data-error="address"></span></label>
            <details class="contribution-disclosure"><summary class="cursor-pointer py-3 font-semibold text-secondary">تلفن، وب‌سایت و ساعت کاری (اختیاری)</summary><div class="space-y-6 pt-3">
                <label class="block">معرفی کوتاه<textarea class="field" name="description" maxlength="3000" rows="4"></textarea><span class="field-error" data-error="description"></span></label>
                <div><div class="flex items-center justify-between"><h3 class="font-bold">شماره‌های تلفن</h3><button type="button" class="button-secondary" data-contribution-add="phones">+ شماره</button></div><div class="mt-3 space-y-2" data-contribution-list="phones"></div></div>
                <div><div class="flex items-center justify-between"><h3 class="font-bold">وب‌سایت‌ها</h3><button type="button" class="button-secondary" data-contribution-add="websites">+ وب‌سایت</button></div><div class="mt-3 space-y-2" data-contribution-list="websites"></div></div>
                <div><label class="flex items-center gap-3 font-bold"><input id="include-hours" type="checkbox" class="size-5 accent-pomegranate">ساعت کاری را می‌دانم</label><p class="mt-2 text-xs leading-6 text-muted">اگر مطمئن نیستی، این بخش را خالی بگذار. در صورت تکمیل، ساعت‌ها به وقت تهران هستند و روزهای تعطیل را مشخص کن.</p><div id="hours-fields" hidden class="mt-3 divide-y divide-border rounded-xl border border-border">@foreach(\App\Services\BusinessHours::DAYS as $day)<div class="grid gap-3 p-3 sm:grid-cols-[6rem_6rem_1fr]" data-contribution-day="{{ $day }}"><strong class="pt-3">{{ \App\Services\BusinessHours::LABELS[$day] }}</strong><label class="flex items-center gap-2"><input type="checkbox" data-contribution-closed class="size-5" checked> تعطیل</label><div><div class="space-y-2" data-contribution-shifts></div><button type="button" class="mt-2 text-sm font-bold text-pomegranate" data-contribution-add-shift>+ افزودن نوبت</button></div></div>@endforeach</div></div>
            </div></details>
            <label class="flex items-start gap-3"><input name="confirm_distinct" type="checkbox" class="mt-1 size-5 accent-pomegranate"><span class="text-sm leading-7">این مکان یا شعبه در نتایج جست‌وجو نبود.</span></label><span class="field-error" data-error="confirm_distinct"></span>
            <button type="button" id="place-only" class="button-secondary w-full">فقط ثبت مکان؛ تجربه‌ای نمی‌نویسم</button>
        </section>
        <section data-step="3" class="panel space-y-5" hidden>
            <h2 id="review-heading" class="text-xl font-bold" tabindex="-1">تجربه شما</h2>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-soft p-4"><div class="flex min-w-0 items-center gap-3"><x-icon name="pin" class="text-pomegranate" /><p id="selected-business" class="font-semibold leading-7"></p></div><button type="button" id="change-business" class="min-h-11 shrink-0 text-sm font-semibold text-pomegranate">تغییر مکان</button></div>
            <label id="review-optional" class="contribution-notice flex items-center gap-3 text-sm"><input class="size-5 accent-pomegranate" type="checkbox" name="with_review" checked>تجربه‌ام را هم می‌نویسم</label>
            <div id="review-fields" class="space-y-5">
                <fieldset><legend class="mb-3 text-sm font-semibold">چطور بود؟ <span class="text-pomegranate">*</span></legend><div class="flex flex-wrap items-center gap-3"><div class="flex gap-1.5">@foreach([1 => 'خیلی بد', 2 => 'بد', 3 => 'متوسط', 4 => 'خوب', 5 => 'عالی'] as $rating => $label)<label data-rating-value="{{ $rating }}" class="contribution-star relative flex size-12 cursor-pointer items-center justify-center rounded-lg bg-soft text-muted transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-pomegranate"><input type="radio" name="rating" value="{{ $rating }}" class="sr-only" aria-label="{{ $rating }} ستاره — {{ $label }}"><x-icon name="star" filled class="size-7" /></label>@endforeach</div><span id="rating-label" class="text-sm font-semibold text-secondary" aria-live="polite">امتیازت را انتخاب کن</span></div><span class="field-error" data-error="rating"></span></fieldset>
                <label class="block font-semibold">تجربه‌ات چطور بود؟ <span class="text-pomegranate">*</span><textarea class="field font-normal" name="body" aria-describedby="body-count" rows="5" minlength="10" maxlength="2000" placeholder="چی دوست داشتی؟ چه چیزی می‌توانست بهتر باشد؟"></textarea><span id="body-count" class="mt-2 block text-xs font-normal text-muted">حداقل ۱۰ حرف</span><span class="field-error" data-error="body"></span></label>
                <details><summary class="cursor-pointer py-2 text-sm text-secondary">تاریخ بازدید — اختیاری</summary><label class="mt-2 block text-sm">تاریخ بازدید (جلالی)<div class="flex items-end gap-2"><input class="field" name="visit_date" inputmode="numeric" placeholder="۱۴۰۵/۰۶/۱۴" dir="ltr"><button type="button" id="open-calendar" class="button-secondary shrink-0">تقویم</button></div><span class="field-error" data-error="visit_date"></span></label></details>
            </div>
            <div class="border-t border-border pt-5"><h3 class="font-semibold">عکس داری؟ <span class="text-xs font-normal text-muted">اختیاری</span></h3><p class="mt-2 text-xs leading-6 text-muted">تا ۶ عکس، هر کدام حداکثر ۱۰ مگابایت · JPG، PNG یا WebP</p></div>
            <div class="flex flex-wrap gap-3"><label class="button-secondary cursor-pointer">انتخاب از گالری<input id="gallery-input" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only"></label><label class="button-secondary cursor-pointer">گرفتن عکس<input id="camera-input" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" class="sr-only"></label></div>
            <p id="featured-photo-help" class="text-xs text-muted" hidden>اولین عکس، تصویر اصلی مکان است؛ می‌توانی آن را عوض کنی.</p>
            <div id="photo-previews" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div><span class="field-error" data-error="photo_ids"></span><span class="field-error" data-error="featured_photo_ids"></span>
        </section>
        <section data-step="4" class="panel space-y-5" hidden>
            <h2 class="text-2xl font-bold" tabindex="-1">بررسی و ثبت نهایی</h2>
            <p class="text-sm leading-7 text-secondary">اطلاعات زیر را بررسی کن. تا وقتی دکمه ثبت نهایی را نزنی، مشارکتت ارسال نمی‌شود.</p>
            <div class="rounded-xl border border-border p-4">
                <div class="flex items-center justify-between gap-3"><h3 class="font-bold">مکان انتخاب‌شده</h3><button type="button" id="edit-place-summary" class="contribution-text-button">ویرایش مکان</button></div>
                <p id="summary-place" class="mt-3 font-semibold"></p><p id="summary-address" class="mt-2 text-sm leading-7 text-secondary"></p>
                <dl id="summary-details" class="mt-3 space-y-2 text-sm leading-7"></dl>
            </div>
            <div class="rounded-xl border border-border p-4">
                <div class="flex items-center justify-between gap-3"><h3 class="font-bold">تجربه و عکس‌ها</h3><button type="button" id="edit-review-summary" class="contribution-text-button">ویرایش</button></div>
                <p id="summary-rating" class="mt-3 font-semibold"></p><p id="summary-body" class="mt-2 whitespace-pre-wrap text-sm leading-7"></p><p id="summary-photos" class="mt-3 text-xs text-secondary"></p>
            </div>
            <div id="display-name-field" hidden><label>نام نمایشی عمومی<input class="field" name="display_name" minlength="2" maxlength="60" autocomplete="nickname"><span class="text-xs text-muted">این نام کنار تجربه شما دیده می‌شود؛ شماره موبایل نمایش داده نمی‌شود.</span><span class="field-error" data-error="display_name"></span></label></div>
            <p id="verified-status" class="rounded-xl bg-positive/10 p-3 text-sm text-positive" role="status" hidden>شماره موبایل تأیید شد. حالا می‌توانی مشارکتت را ثبت نهایی کنی.</p>
            <div id="otp-panel" class="contribution-notice" hidden>
                <h3 class="font-bold">تأیید شماره موبایل</h3><p class="mt-2 text-sm leading-7">برای ثبت مشارکت، شماره‌ات را تأیید کن. شماره موبایل عمومی نمی‌شود؛ پس از تأیید، دکمه ثبت نهایی را می‌زنی.</p>
                <label class="mt-4 block">شماره موبایل<input id="otp-mobile" class="field" type="tel" autocomplete="tel" inputmode="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" aria-describedby="mobile-error"></label><span class="field-error" data-error="mobile" id="mobile-error"></span>
                <button type="button" id="send-otp" class="button-secondary mt-3">دریافت کد ورود</button>
                <div id="otp-code-panel" hidden><label class="mt-4 block">کد پنج‌رقمی<input id="otp-code" class="field" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="5" dir="ltr" aria-describedby="code-error"></label><span class="field-error" data-error="code" id="code-error"></span><button type="button" id="verify-otp" class="button-primary mt-3">تأیید شماره</button></div>
                <p id="otp-status" class="mt-3 text-sm" role="status"></p>
            </div>
            <p id="submission-summary" class="contribution-notice text-sm leading-7"></p>
        </section>
        <p id="signin-hint" class="mt-5 text-center text-xs leading-6 text-muted" @auth hidden @endauth>برای ثبت مشارکت، در پایان شماره موبایلت را با یک کد پیامکی تأیید می‌کنی.</p>
        <div id="step-actions" class="contribution-actions sticky bottom-0 z-10 mt-5 flex items-center justify-between gap-3 rounded-xl border border-border bg-canvas/95 p-3 backdrop-blur" hidden>
            <button type="button" id="previous-step" class="button-secondary" hidden>مرحله قبل</button>
            <button type="button" id="next-step" class="button-primary mr-auto">ادامه</button>
        </div>
    </form>
    <div id="contribution-success" class="panel border-positive/30 text-center" hidden><div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-full bg-positive/10 text-2xl text-positive" aria-hidden="true">✓</div><h2 id="success-heading" tabindex="-1" class="text-2xl font-bold"></h2><p id="success-copy" class="my-4 leading-8"></p><a id="success-link" class="button-primary">مشاهده مشارکت</a><a href="{{ route('contribute', ['new' => 1]) }}" class="button-secondary mt-3">مشارکت تازه</a></div>
    <noscript><p class="panel">برای حفظ عکس‌ها و پیش‌نویس، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
    <dialog id="jalali-calendar" class="m-auto w-[min(95vw,420px)] rounded-2xl border border-border bg-surface p-5 backdrop:bg-ink/40">
        <h2 class="text-lg font-bold">انتخاب تاریخ بازدید</h2><div class="my-4 grid grid-cols-2 gap-3"><label>سال<select id="calendar-year" class="field"></select></label><label>ماه<select id="calendar-month" class="field"></select></label></div><div id="calendar-days" class="grid grid-cols-7 gap-1"></div><button type="button" id="close-calendar" class="button-secondary mt-4">بستن</button>
    </dialog>
</div>
@endsection
