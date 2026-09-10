@extends('layouts.admin')

@section('title', 'شهرها')

@section('admin-content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-extrabold">شهرها</h1><p class="mt-2 text-secondary">شهرهای قابل انتخاب در ثبت و کشف مکان‌ها را مدیریت کنید.</p></div>
        <a class="button-primary" href="{{ route('admin.cities.create') }}">افزودن شهر</a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($cities as $city)
            <article class="panel flex flex-wrap items-center gap-4">
                <div class="min-w-0 flex-1"><h2 class="font-bold">{{ $city->name }}</h2><p class="mt-1 text-sm text-muted">ترتیب {{ $city->position }} · @if($city->latitude !== null)<bdi dir="ltr">{{ $city->latitude }}, {{ $city->longitude }}</bdi>@else بدون مختصات @endif</p></div>
                <span class="admin-status">{{ $city->is_active ? 'فعال' : 'غیرفعال' }}</span>
                <a class="button-secondary" href="{{ route('admin.cities.edit', $city) }}">مدیریت</a>
            </article>
        @empty
            <p class="panel text-muted">شهری ثبت نشده است.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $cities->links() }}</div>
@endsection
