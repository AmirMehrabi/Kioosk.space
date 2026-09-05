@props(['value' => 5, 'size' => 'small', 'dynamic' => null])
<span {{ $attributes->class(['inline-flex gap-1 align-middle']) }} role="img" @if($dynamic) :aria-label="fa({{ $dynamic }}) + ' از ۵ ستاره'" @endif aria-label="{{ $value }} از ۵ ستاره">
    @for ($star = 1; $star <= 5; $star++)
        <span @if($dynamic) :class="{{ $star }} <= Math.round({{ $dynamic }}) ? 'bg-pomegranate text-white' : 'bg-border text-white'" @endif @class(['inline-flex items-center justify-center rounded-[5px]', 'size-6' => $size === 'small', 'size-8' => $size === 'large', 'bg-pomegranate text-white' => !$dynamic && $star <= round($value), 'bg-border text-white' => !$dynamic && $star > round($value)])><x-icon name="star" :filled="true" class="size-4" /></span>
    @endfor
</span>
