@props(['items' => []])

@if(count($items))
    <nav aria-label="مسیر صفحه" {{ $attributes->class(['mb-6']) }}>
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs leading-6 text-muted sm:text-sm">
            <li><a href="{{ route('home') }}" class="inline-flex min-h-8 items-center rounded hover:text-pomegranate">خانه</a></li>
            @foreach($items as $item)
                <li class="flex min-w-0 items-center gap-2">
                    <svg class="size-3 shrink-0 text-muted/60" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m10 4-4 4 4 4" /></svg>
                    @if(isset($item['url']) && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="inline-flex min-h-8 items-center rounded hover:text-pomegranate">{{ $item['label'] }}</a>
                    @else
                        <span aria-current="page" class="min-w-0 break-words font-medium text-secondary">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
