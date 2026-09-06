@props(['rating' => 0])
<span {{ $attributes->class(['inline-flex items-center gap-1']) }} role="img" aria-label="{{ $rating }} از ۵ ستاره">
    @for($star = 1; $star <= 5; $star++)<span @class(['inline-flex size-7 items-center justify-center rounded-md text-sm', 'bg-pomegranate text-white' => $star <= round($rating), 'bg-soft text-muted' => $star > round($rating)]) aria-hidden="true">★</span>@endfor
</span>
