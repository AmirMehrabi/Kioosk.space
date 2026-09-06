@extends('layouts.community')
@section('breadcrumbs')
    <x-breadcrumbs :items="auth()->check() ? [['label' => 'مشارکت‌های من', 'url' => route('contributions.index')], ['label' => request()->routeIs('reviews.edit') ? 'ویرایش تجربه من' : 'افزودن مکان یا نوشتن نظر']] : [['label' => 'افزودن مکان یا نوشتن نظر']]" />
@endsection
@section('title', 'افزودن مکان یا نوشتن نظر')
@section('content')
<div id="contribution-app" class="mx-auto max-w-2xl" data-user="{{ auth()->id() }}" data-generic="{{ auth()->check() && preg_match('/^(کاربر|User)(\s|$)/u', auth()->user()->name) ? '1' : '0' }}">
    <script id="contribution-initial" type="application/json">{!! json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <h1 class="text-2xl font-bold">افزودن مکان یا نوشتن نظر</h1>
    <p class="mt-3 leading-7 text-secondary">از هر جای ایران، تجربه شما به انتخاب بهتر دیگران کمک می‌کند.</p>
    <ol id="stepper" aria-label="مراحل مشارکت" class="my-6 grid grid-cols-4 gap-2 text-center text-xs sm:text-sm">
        @foreach(['پیدا کردن', 'اطلاعات مکان', 'تجربه شما', 'ثبت نهایی'] as $step)<li class="rounded-xl bg-soft px-1 py-3" data-step-label="{{ $loop->iteration }}">{{ $loop->iteration }}. {{ $step }}</li>@endforeach
    </ol>
    <p id="draft-status" class="mb-4 text-sm text-muted" role="status" aria-live="polite">در حال بازیابی پیش‌نویس…</p>
    <p id="contribution-error" role="alert" tabindex="-1" class="field-error mb-4" hidden></p>
    <div id="conflict" class="panel mb-4" hidden><p>تجربه قبلی شما موجود است. این پیش‌نویس محفوظ می‌ماند.</p><a id="edit-existing" class="button-secondary mt-3">ویرایش تجربه من</a></div>
    <div id="draft-conflict" class="panel mb-4" hidden><p>نسخه این دستگاه را نگه دارید و تغییرات ذخیره‌شده در حساب را بررسی کنید.</p><a id="reload-draft" target="_blank" rel="noopener" class="button-secondary mt-3">بررسی نسخه حساب در پنجره تازه</a></div>
    <form id="contribution-form" novalidate>
        <section data-step="1" class="panel space-y-5">
            <h2 class="text-xl font-bold" tabindex="-1">اول مکان را پیدا کنید</h2>
            <label class="block">نام مکان<input class="field" id="search-name" type="search" maxlength="180" autocomplete="off"></label>
            <x-city-select :cities="$cities" id="search-city" name="search_city" label="شهر" />
            <div class="flex flex-wrap gap-3"><button type="button" id="search-businesses" class="button-primary">جست‌وجوی مکان</button><button type="button" id="locate" class="button-secondary">استفاده از موقعیت من</button></div>
            <p id="gps-status" class="text-sm text-muted" role="status">موقعیت فقط با درخواست شما دریافت می‌شود.</p>
            <div id="search-results" class="space-y-3" aria-live="polite"></div>
            <button type="button" id="search-more" class="button-secondary" hidden>نتایج بیشتر</button>
            <button type="button" id="new-business" class="button-secondary w-full">مکان مورد نظر نیست؛ افزودن مکان جدید</button>
        </section>
        <section data-step="2" class="panel space-y-5" hidden>
            <h2 class="text-xl font-bold" tabindex="-1">اطلاعات مکان جدید</h2>
            <label class="block">نام مکان <span class="text-pomegranate">*</span><input class="field" name="name" maxlength="180"><span class="field-error" data-error="name"></span></label>
            <label class="block">دسته‌بندی <span class="text-pomegranate">*</span><select class="field" name="category_id"><option value="">انتخاب کنید</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select><span class="field-error" data-error="category_id"></span></label>
            <x-city-select :cities="$cities" id="city" name="city" label="شهر" required hint="شهر خود را از فهرست انتخاب کنید."><span class="field-error" data-error="city"></span></x-city-select>
            <label class="block">آدرس کوتاه <span class="text-pomegranate">*</span><input class="field" name="address" maxlength="500"><span class="field-error" data-error="address"></span></label>
            <details><summary class="cursor-pointer py-3 text-secondary">اطلاعات اختیاری؛ تماس و ساعت کار</summary><div class="space-y-4 pt-3">
                <label class="block">تلفن<input class="field" name="phone" type="tel" maxlength="40" dir="ltr"><span class="field-error" data-error="phone"></span></label>
                <label class="block">وب‌سایت<input class="field" name="website" type="url" maxlength="500" placeholder="https://" dir="ltr"><span class="field-error" data-error="website"></span></label>
                <label class="block">ساعت کار به وقت تهران<textarea class="field" name="opening_hours" maxlength="1000" rows="2"></textarea></label>
            </div></details>
            <label class="flex items-start gap-3"><input name="confirm_distinct" type="checkbox" class="mt-1 size-5"><span class="text-sm leading-7">نتایج جست‌وجو را بررسی کردم؛ این مکان یا شعبه با مکان‌های مشابه متفاوت است.</span></label><span class="field-error" data-error="confirm_distinct"></span>
        </section>
        <section data-step="3" class="panel space-y-5" hidden>
            <h2 id="review-heading" class="text-xl font-bold" tabindex="-1">تجربه شما</h2>
            <p id="selected-business" class="text-secondary"></p>
            <label id="review-optional" class="flex items-center gap-3"><input class="size-5" type="checkbox" name="with_review" checked>همراه مکان، تجربه‌ام را هم ثبت می‌کنم</label>
            <div id="review-fields" class="space-y-5">
                <fieldset><legend class="mb-3">امتیاز شما <span class="text-pomegranate">*</span></legend><div class="flex gap-2">@for($rating=1;$rating<=5;$rating++)<label class="flex min-h-12 flex-1 cursor-pointer items-center justify-center gap-1 rounded-xl border border-border p-2 has-checked:border-pomegranate has-checked:bg-pomegranate/10"><input type="radio" name="rating" value="{{ $rating }}" class="size-4"><span>{{ $rating }} ★</span></label>@endfor</div><span class="field-error" data-error="rating"></span></fieldset>
                <label class="block">تجربه شما <span class="text-pomegranate">*</span><textarea class="field" name="body" rows="6" minlength="10" maxlength="2000" placeholder="چه چیزی در تجربه شما خوب بود و چه چیزی می‌تواند بهتر شود؟"></textarea><span class="text-xs text-muted">۱۰ تا ۲۰۰۰ نویسه؛ عنوان لازم نیست.</span><span class="field-error" data-error="body"></span></label>
                <label class="block">تاریخ بازدید (جلالی)<div class="flex items-end gap-2"><input class="field" name="visit_date" inputmode="numeric" placeholder="۱۴۰۵/۰۶/۱۴" dir="ltr"><button type="button" id="open-calendar" class="button-secondary shrink-0">تقویم</button></div><span class="field-error" data-error="visit_date"></span></label>
            </div>
        </section>
        <section data-step="4" class="panel space-y-5" hidden>
            <h2 class="text-xl font-bold" tabindex="-1">عکس‌ها و ثبت نهایی</h2>
            <p class="text-sm leading-7 text-secondary">تا شش عکس JPEG، PNG یا WebP، هر عکس حداکثر ۱۰ مگابایت. عکس‌ها اختیاری هستند.</p>
            <div class="flex flex-wrap gap-3"><label class="button-secondary cursor-pointer">انتخاب از گالری<input id="gallery-input" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only"></label><label class="button-secondary cursor-pointer">گرفتن عکس<input id="camera-input" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" class="sr-only"></label></div>
            <div id="photo-previews" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div><span class="field-error" data-error="photo_ids"></span>
            <div id="display-name-field" hidden><label>نام نمایشی عمومی<input class="field" name="display_name" minlength="2" maxlength="60" autocomplete="nickname"><span class="text-xs text-muted">این نام کنار تجربه شما دیده می‌شود؛ شماره موبایل نمایش داده نمی‌شود.</span><span class="field-error" data-error="display_name"></span></label></div>
            <div id="otp-panel" class="rounded-xl bg-soft p-4" hidden>
                <h3 class="font-bold">ورود برای ثبت نهایی</h3><p class="mt-2 text-sm leading-7">پیش‌نویس و عکس‌ها در همین صفحه محفوظ می‌مانند.</p>
                <label class="mt-4 block">شماره موبایل<input id="otp-mobile" class="field" type="tel" autocomplete="tel" dir="ltr"></label>
                <button type="button" id="send-otp" class="button-secondary mt-3">دریافت کد ورود</button>
                <div id="otp-code-panel" hidden><label class="mt-4 block">کد پنج‌رقمی<input id="otp-code" class="field" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="5" dir="ltr"></label><button type="button" id="verify-otp" class="button-primary mt-3">تأیید و ادامه</button></div>
                <p id="otp-status" class="mt-3 text-sm" role="status"></p>
            </div>
            <p id="submission-summary" class="text-sm leading-7 text-secondary"></p>
        </section>
        <div id="step-actions" class="sticky bottom-0 z-10 mt-5 flex items-center justify-between gap-3 rounded-xl border border-border bg-canvas/95 p-3 backdrop-blur">
            <button type="button" id="previous-step" class="button-secondary" hidden>مرحله قبل</button>
            <button type="button" id="next-step" class="button-primary mr-auto">ادامه</button>
        </div>
    </form>
    <div id="contribution-success" class="panel" hidden><h2 id="success-heading" class="text-2xl font-bold"></h2><p id="success-copy" class="my-4 leading-8"></p><a id="success-link" class="button-primary">مشاهده مشارکت</a><a href="{{ route('contribute', ['new' => 1]) }}" class="button-secondary mt-3">مشارکت تازه</a></div>
    <noscript><p class="panel">برای حفظ عکس‌ها و پیش‌نویس، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
    <dialog id="jalali-calendar" class="m-auto w-[min(95vw,420px)] rounded-2xl border border-border bg-surface p-5 backdrop:bg-ink/40">
        <h2 class="text-lg font-bold">انتخاب تاریخ بازدید</h2><div class="my-4 grid grid-cols-2 gap-3"><label>سال<select id="calendar-year" class="field"></select></label><label>ماه<select id="calendar-month" class="field"></select></label></div><div id="calendar-days" class="grid grid-cols-7 gap-1"></div><button type="button" id="close-calendar" class="button-secondary mt-4">بستن</button>
    </dialog>
</div>
@endsection
