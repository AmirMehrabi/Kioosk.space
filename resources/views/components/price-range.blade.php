@props(['value' => null])
@if(isset(\App\Models\Business::PRICE_RANGES[$value]))
    <span {{ $attributes->class(['inline-flex items-center gap-1 text-secondary']) }} title="{{ \App\Models\Business::PRICE_RANGES[$value] }}" aria-label="بازه قیمت: {{ \App\Models\Business::PRICE_RANGES[$value] }}">
        <bdi dir="ltr" class="font-semibold tracking-wider" aria-hidden="true">{{ str_repeat('$', $value) }}<span class="text-muted/40">{{ str_repeat('$', 4 - $value) }}</span></bdi>
        <span class="sr-only">{{ \App\Models\Business::PRICE_RANGES[$value] }}</span>
    </span>
@endif
