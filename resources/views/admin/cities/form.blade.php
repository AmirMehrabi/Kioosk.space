@extends('layouts.admin')

@section('title', $city->exists ? 'مدیریت شهر' : 'افزودن شهر')

@section('admin-content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold">{{ $city->exists ? $city->name : 'افزودن شهر' }}</h1>
            <p class="mt-2 text-secondary">مختصات، نمایش روی نقشه و ترتیب فهرست را از همین صفحه کنترل کنید.</p>
        </div>
        <a class="button-secondary" href="{{ route('admin.cities.index') }}">بازگشت به شهرها</a>
    </div>

    <form method="post" action="{{ $city->exists ? route('admin.cities.update', $city) : route('admin.cities.store') }}" class="panel mt-6 grid gap-4 sm:grid-cols-2">
        @csrf
        @if ($city->exists)
            @method('put')
        @endif

        <label class="sm:col-span-2">
            نام شهر
            <input class="field" name="name" required maxlength="100" value="{{ old('name', $city->name) }}">
        </label>
        <label>
            عرض جغرافیایی
            <input class="field" name="latitude" inputmode="decimal" dir="ltr" value="{{ old('latitude', $city->latitude) }}">
        </label>
        <label>
            طول جغرافیایی
            <input class="field" name="longitude" inputmode="decimal" dir="ltr" value="{{ old('longitude', $city->longitude) }}">
        </label>
        <label>
            ترتیب نمایش
            <input class="field" name="position" type="number" min="0" value="{{ old('position', $city->position ?: $nextPosition) }}">
        </label>

        @if ($city->exists)
            <label class="flex min-h-11 items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $city->is_active))>
                فعال و قابل انتخاب
            </label>
        @endif

        <div class="sm:col-span-2 flex flex-wrap justify-between gap-3 border-t border-border pt-4">
            <button class="button-primary">{{ $city->exists ? 'ذخیره تغییرات' : 'افزودن شهر' }}</button>
            @if ($city->exists)
                <button type="submit" form="delete-city" class="button-secondary text-pomegranate">حذف شهر</button>
            @endif
        </div>
    </form>

    @if ($city->exists)
        <form id="delete-city" method="post" action="{{ route('admin.cities.destroy', $city) }}">
            @csrf
            @method('delete')
        </form>
    @endif

    @foreach (['name', 'latitude', 'longitude', 'position'] as $field)
        @error($field)
            <p class="field-error mt-3">{{ $message }}</p>
        @enderror
    @endforeach
@endsection
