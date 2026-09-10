@extends('layouts.admin')

@section('title', 'دسته‌بندی‌ها')

@section('admin-content')
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-3xl font-extrabold">دسته‌بندی‌ها</h1><p class="mt-2 text-secondary">دسته‌های قابل انتخاب برای مکان‌ها را مدیریت کنید.</p></div><a class="button-primary" href="{{ route('admin.categories.create') }}">افزودن دسته‌بندی</a></div>
    <div class="mt-6 space-y-3">@forelse($categories as $category)<article class="panel flex flex-wrap items-center gap-4"><div class="min-w-0 flex-1"><h2 class="font-bold">{{ $category->name }}</h2><p class="mt-1 text-sm text-muted">ترتیب نمایش {{ $category->position }}</p></div><span class="admin-status">{{ $category->is_active ? 'فعال' : 'غیرفعال' }}</span><a class="button-secondary" href="{{ route('admin.categories.edit', $category) }}">مدیریت</a></article>@empty<p class="panel text-muted">دسته‌بندی ثبت نشده است.</p>@endforelse</div>
    <div class="mt-6">{{ $categories->links() }}</div>
@endsection
