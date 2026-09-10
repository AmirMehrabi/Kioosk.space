@props(['name', 'filled' => false])
<svg {{ $attributes->class(['size-5 shrink-0']) }} viewBox="0 0 24 24" fill="{{ $filled ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('utensils') <path d="M4 3v5a3 3 0 0 0 6 0V3M7 3v18M17 3c-2 3-3 7-3 10h5M19 3v18"/> @break
        @case('shopping-bag') <path d="M4 7h16l1 14H3ZM8 8V6a4 4 0 0 1 8 0v2"/> @break
        @case('medical') <rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 5V3h8v2M12 10v6m-3-3h6"/> @break
        @case('sparkles') <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5ZM3 3v4M1 5h4m15 13v4m-2-2h4"/> @break
        @case('home') <path d="m3 11 9-8 9 8M5 9v12h14V9M9 21v-8h6v8"/> @break
        @case('compass') <circle cx="12" cy="12" r="9"/><path d="m16 8-2 6-6 2 2-6Z"/> @break
        @case('map') <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15m6-12v15"/> @break
        @case('star') <path d="m12 3 2.8 5.7 6.3.9-4.5 4.4 1.1 6.2-5.7-3-5.7 3 1.1-6.2L3 9.6l6.2-.9Z"/> @break
        @case('crown') <path d="m3 7 4 4 5-7 5 7 4-4-2 12H5L3 7Z"/><path d="M5 19h14"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/> @break
        @case('pin') <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/> @break
        @case('heart') <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/> @break
        @case('share') <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4M8.6 13.5l6.8 4"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('badge') <path d="m12 2 3 2 3.5.5.5 3.5 2 4-2 3-.5 3.5-3.5.5-3 3-3-3-3.5-.5L5 15l-2-3 2-4 .5-3.5L9 4Z"/><path d="m8 12 3 3 5-6"/> @break
        @case('arrow-left') <path d="M19 12H5m7-7-7 7 7 7"/> @break
        @case('chevron-left') <path d="m15 6-6 6 6 6"/> @break
        @case('chevron-right') <path d="m9 6 6 6-6 6"/> @break
        @case('chevron-down') <path d="m6 9 6 6 6-6"/> @break
        @case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
        @case('camera') <path d="M3 6h5l2-3h4l2 3h5v15H3Z"/><circle cx="12" cy="13" r="4"/> @break
        @case('grid') <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> @break
        @case('phone') <path d="m5 3 4 1 1 5-3 2a15 15 0 0 0 6 6l2-3 5 1 1 4c0 2-3 3-5 2A21 21 0 0 1 3 8C2 6 3 3 5 3Z"/> @break
        @case('wifi') <path d="M2 8a16 16 0 0 1 20 0M5 12a11 11 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 20h.01"/> @break
        @case('sun') <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/> @break
        @case('coffee') <path d="M4 8h12v7a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5ZM16 9h2a3 3 0 0 1 0 6h-2M7 2v3m4-3v3m4-3v3"/> @break
        @case('card') <rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20M6 15h3"/> @break
        @case('users') <circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M17 3a4 4 0 0 1 0 8m3 10v-3a7 7 0 0 0-3-6"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/> @break
        @case('leaf') <path d="M20 3c-8-2-15 3-15 9a7 7 0 0 0 7 7c6 0 9-8 8-16ZM3 21 15 9"/> @break
        @case('message') <path d="M21 11a8 8 0 0 1-8 8H8l-5 3V7a4 4 0 0 1 4-4h6a8 8 0 0 1 8 8Z"/><path d="M7 8h9M7 12h6"/> @break
        @case('thumb') <path d="M7 10 11 3h2v7h6a2 2 0 0 1 2 2l-2 7a2 2 0 0 1-2 2H7ZM3 10h4v11H3Z"/> @break
        @case('flag') <path d="M4 22V3m0 0c6-5 10 5 16 0v11c-6 5-10-5-16 0"/> @break
        @case('edit') <path d="m15 4 5 5M3 21l5-1L21 7l-5-5L3 15ZM3 21h7"/> @break
        @case('trash') <path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/> @break
        @case('store') <path d="M3 10V7l2-4h14l2 4v3a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0ZM4 13v8h16v-8M9 21v-6h6v6"/> @break
        @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('paw') <circle cx="7" cy="8" r="2"/><circle cx="17" cy="8" r="2"/><circle cx="4" cy="13" r="2"/><circle cx="20" cy="13" r="2"/><path d="M8 19c0-3 1.8-6 4-6s4 3 4 6c0 2-1.8 3-4 1.5C9.8 22 8 21 8 19Z"/> @break
        @case('smoking') <path d="M3 15h14v4H3Zm14 0h2v4h-2Zm2 0c2 0 2-3 0-3m-5 0c2-1 2-3 0-4s-2-3 0-4"/> @break
        @case('chair') <path d="M6 12V6a3 3 0 0 1 6 0v6m-8 0h14a2 2 0 0 1 2 2v3H4v-5Zm2 5v4m12-4v4"/> @break
        @case('parking') <path d="M6 21V3h7a5 5 0 0 1 0 10H6m0-5h7"/> @break
        @case('accessibility') <circle cx="12" cy="4" r="2"/><path d="M7 8h10m-5 0v5m0 0-4 8m4-8 5 8"/> @break
        @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-13 4h2m4 0h2m-8 3h2"/> @break
        @case('bag') <path d="M5 8h14l-1 13H6L5 8Zm4 0V6a3 3 0 0 1 6 0v2"/> @break
        @case('delivery') <path d="M3 6h11v11H3Zm11 4h4l3 4v3h-7Zm-8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/> @break
        @case('restroom') <circle cx="7" cy="4" r="2"/><path d="M7 7v7m-3-4h6m-5 11 2-7 2 7M17 2v19m-3-11h6"/> @break
        @case('music') <path d="M9 18V5l10-2v13M9 9l10-2M6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm10-2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/> @break
    @endswitch
</svg>
