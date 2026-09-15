@props(['value' => 5, 'size' => 'small', 'dynamic' => null])

@php
    $rating = max(0, min(5, (float) $value));
    $ratingColor = match (true) {
        $rating >= 4.5 => '#a92330',
        $rating >= 3.5 => '#c8323e',
        $rating >= 2.5 => '#e4534f',
        $rating >= 1.5 => '#f36b52',
        $rating > 0 => '#ff8a65',
        default => '#d6d1c9',
    };
    $dynamicColor = "(Number({$dynamic}) >= 4.5 ? '#a92330' : Number({$dynamic}) >= 3.5 ? '#c8323e' : Number({$dynamic}) >= 2.5 ? '#e4534f' : Number({$dynamic}) >= 1.5 ? '#f36b52' : Number({$dynamic}) > 0 ? '#ff8a65' : '#d6d1c9')";
@endphp

<span
    {{ $attributes->class(['inline-flex items-center gap-1.5 align-middle']) }}
    role="img"
    data-rating="{{ $rating }}"
    data-rating-color="{{ $ratingColor }}"
    data-rating-size="{{ $size }}"
    @if($dynamic) x-bind:aria-label="fa({{ $dynamic }}) + ' از ۵ ستاره'" @else aria-label="{{ $rating }} از ۵ ستاره" @endif
>
    @for ($star = 1; $star <= 5; $star++)
        @php($fill = max(0, min(1, $rating - ($star - 1))) * 100)
        <span
            @class([
                'relative inline-flex items-center justify-center overflow-hidden bg-soft shadow-[inset_0_0_0_1px_rgb(23_23_23_/_6%)]',
                'size-8 rounded-[9px]' => $size === 'small',
                'size-10 rounded-[11px]' => $size === 'large',
            ])
            data-rating-star="{{ $star }}"
            data-fill="{{ $fill }}"
            aria-hidden="true"
        >
            <x-icon name="star" filled @class(['absolute inset-0 m-auto text-muted/55', 'size-5' => $size === 'small', 'size-6' => $size === 'large']) />
            <span
                class="absolute inset-y-0 right-0 overflow-hidden"
                @if($dynamic)
                    x-bind:style="`width: ${Math.max(0, Math.min(1, Number({{ $dynamic }}) - {{ $star - 1 }})) * 100}%; background-color: ${{ $dynamicColor }}`"
                @else
                    style="width: {{ $fill }}%; background-color: {{ $ratingColor }}"
                @endif
            >
                <x-icon name="star" filled @class(['absolute inset-y-0 my-auto max-w-none text-white', 'right-1.5 size-5' => $size === 'small', 'right-2 size-6' => $size === 'large']) />
            </span>
        </span>
    @endfor
</span>
