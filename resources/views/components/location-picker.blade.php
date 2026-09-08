@props(['cities', 'latitude' => null, 'longitude' => null, 'cityInput' => 'management-city'])
<section data-location-picker data-city-input="{{ $cityInput }}" class="panel">
    <script type="application/json" data-map-cities>{!! json_encode($cities->map->only(['name', 'latitude', 'longitude']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-xl font-bold">موقعیت روی نقشه</h2><p class="mt-2 text-sm leading-7 text-secondary">روی محل دقیق کسب‌وکار بزنید یا نشان را جابه‌جا کنید.</p></div>
        <button type="button" data-use-location class="button-secondary"><x-icon name="pin" />موقعیت فعلی من</button>
    </div>
    <div data-location-map class="relative isolate mt-5 h-80 overflow-hidden rounded-xl border border-border bg-soft sm:h-96" role="region" aria-label="انتخاب موقعیت کسب‌وکار"></div>
    <p data-location-status role="status" class="mt-3 text-sm leading-7 text-secondary">مختصات را وارد کنید یا پس از بارگذاری نقشه، محل را انتخاب کنید.</p>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" data-use-map-center class="button-secondary">انتخاب مرکز نقشه</button>
        <button type="button" data-clear-location class="button-secondary">حذف موقعیت</button>
    </div>
    <details class="mt-4" @if($errors->hasAny(['latitude', 'longitude'])) open @endif>
        <summary class="min-h-11 cursor-pointer py-3 text-sm font-semibold">مختصات دقیق</summary>
        <div class="grid gap-4 sm:grid-cols-2">
            <label>عرض جغرافیایی<input data-latitude class="field" name="latitude" type="number" step="any" min="24" max="41" dir="ltr" value="{{ $latitude }}">@error('latitude')<span class="field-error">{{ $message }}</span>@enderror</label>
            <label>طول جغرافیایی<input data-longitude class="field" name="longitude" type="number" step="any" min="43" max="64" dir="ltr" value="{{ $longitude }}">@error('longitude')<span class="field-error">{{ $message }}</span>@enderror</label>
        </div>
    </details>
    <p class="mt-3 text-xs text-muted">موقعیت پس از «ذخیره همه تغییرات» منتشر می‌شود.</p>
</section>
