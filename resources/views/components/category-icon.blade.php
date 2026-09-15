@props(['name'])
<svg
    {{ $attributes->class(['size-14 shrink-0']) }}
    viewBox="0 0 48 48"
    fill="none"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-category-icon="{{ $name }}"
    data-icon-palette="light-dark-red"
>
    @switch($name)
        @case('restaurant')
            <circle cx="24" cy="24" r="14" class="fill-soft stroke-ink" stroke-width="2.2" />
            <circle cx="24" cy="24" r="8" class="stroke-pomegranate" stroke-width="2.2" />
            <path d="M7 12v9c0 3 2 5 5 5s5-2 5-5v-9M12 12v28M35 12c-3 4-4 9-4 14h7M38 12v28" class="stroke-ink" stroke-width="2.2" />
            <path d="M9.5 12v8M14.5 12v8M31 26h7" class="stroke-pomegranate" stroke-width="2.2" />
            @break
        @case('cafe')
            <path d="M10 18h24v11c0 7-5 11-12 11s-12-4-12-11V18Z" class="fill-soft stroke-ink" stroke-width="2.2" />
            <path d="M34 21h2a6 6 0 0 1 0 12h-3" class="stroke-pomegranate" stroke-width="2.2" />
            <path d="M8 40h30M17 13c-3-3 3-4 0-7M25 13c-3-3 3-4 0-7" class="stroke-ink" stroke-width="2.2" />
            <path d="M32 13c-3-3 3-4 0-7" class="stroke-pomegranate" stroke-width="2.2" />
            @break
        @case('shopping')
            <path d="M9 16h30l-2 25H11L9 16Z" class="fill-soft stroke-ink" stroke-width="2.2" />
            <path d="M17 18v-5a7 7 0 0 1 14 0v5" class="stroke-pomegranate" stroke-width="2.2" />
            <path d="m24 23 2.2 4.5 5 .7-3.6 3.5.8 5-4.4-2.4-4.4 2.4.8-5-3.6-3.5 5-.7L24 23Z" class="fill-pomegranate stroke-pomegranate" stroke-width="1.6" />
            @break
        @case('medical')
            <rect x="7" y="14" width="34" height="27" rx="6" class="fill-soft stroke-ink" stroke-width="2.2" />
            <path d="M17 14v-3a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v3M7 23h34" class="stroke-ink" stroke-width="2.2" />
            <path d="M21 27h6v4h4v6h-4v4h-6v-4h-4v-6h4v-4Z" class="fill-pomegranate" />
            @break
        @case('beauty')
            <circle cx="19" cy="19" r="11" class="fill-soft stroke-ink" stroke-width="2.2" />
            <path d="m27 27 9 9M33 33l-5 5" class="stroke-ink" stroke-width="2.2" />
            <path d="m36 7 1.6 4.4L42 13l-4.4 1.6L36 19l-1.6-4.4L30 13l4.4-1.6L36 7Z" class="fill-pomegranate stroke-pomegranate" stroke-width="1.5" />
            <path d="M13 20c2 3 7 4 11 1" class="stroke-pomegranate" stroke-width="2.2" />
            @break
        @case('home-services')
            <path d="m6 22 18-15 18 15v19H6V22Z" class="fill-soft stroke-ink" stroke-width="2.2" />
            <path d="M18 41V28h12v13" class="stroke-ink" stroke-width="2.2" />
            <path d="M35 8a7 7 0 0 0-6 10L17 30a4 4 0 1 0 5 5l12-12a7 7 0 0 0 8-9l-5 5-4-1-1-4 5-5c-.7-.5-1.3-.8-2-1Z" class="fill-surface stroke-pomegranate" stroke-width="2.2" />
            @break
        @case('tourism')
            <rect x="9" y="16" width="30" height="25" rx="5" class="fill-soft stroke-ink" stroke-width="2.2" />
            <path d="M18 16v-4a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v4M16 20v17M32 20v17" class="stroke-ink" stroke-width="2.2" />
            <path d="M24 22c-4 0-7 3-7 7 0 5 7 9 7 9s7-4 7-9c0-4-3-7-7-7Z" class="fill-surface stroke-pomegranate" stroke-width="2.2" />
            <circle cx="24" cy="29" r="2" class="fill-pomegranate" />
            @break
        @default
            <rect x="7" y="7" width="14" height="14" rx="4" class="fill-soft stroke-ink" stroke-width="2.2" />
            <rect x="27" y="7" width="14" height="14" rx="7" class="fill-pomegranate stroke-pomegranate" stroke-width="2.2" />
            <rect x="7" y="27" width="14" height="14" rx="7" class="fill-surface stroke-pomegranate" stroke-width="2.2" />
            <path d="m34 27 7 7-7 7-7-7 7-7Z" class="fill-soft stroke-ink" stroke-width="2.2" />
    @endswitch
</svg>
