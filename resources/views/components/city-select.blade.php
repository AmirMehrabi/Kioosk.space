@props(['cities', 'id' => 'city', 'name' => 'city', 'value' => '', 'label' => 'شهر', 'required' => false, 'hint' => null])
@php($optionsId = $id.'-options')

<div data-city-select class="relative">
    <label for="{{ $id }}" class="block">{{ $label }} @if($required)<span class="text-pomegranate">*</span>@endif</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="search" value="{{ $value }}" maxlength="100" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $optionsId }}" @if($required) required @endif {{ $attributes->merge(['class' => 'field']) }}>
        <div id="{{ $optionsId }}" role="listbox" class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-xl border border-border bg-surface p-1 shadow-soft" data-city-options>
            @foreach($cities as $city)
                <button type="button" role="option" aria-selected="false" data-city-option value="{{ $city->name }}" class="block w-full rounded-lg px-3 py-3 text-right text-sm hover:bg-soft">{{ $city->name }}</button>
            @endforeach
            <p class="hidden px-3 py-3 text-sm text-muted" data-city-empty>شهری پیدا نشد.</p>
        </div>
    </div>
    @if($hint)<p class="mt-2 text-xs text-muted">{{ $hint }}</p>@endif
    {{ $slot }}
</div>
