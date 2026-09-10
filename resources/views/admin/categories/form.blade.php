@extends('layouts.admin')

@section('title', $category->exists ? 'مدیریت دسته‌بندی' : 'افزودن دسته‌بندی')

@section('admin-content')
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-3xl font-extrabold">{{ $category->exists ? $category->name : 'افزودن دسته‌بندی' }}</h1><p class="mt-2 text-secondary">نام، ترتیب نمایش و دسترس‌پذیری دسته را از همین صفحه کنترل کنید.</p></div><a class="button-secondary" href="{{ route('admin.categories.index') }}">بازگشت به دسته‌بندی‌ها</a></div>
    <form method="post" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="panel mt-6 grid gap-4 sm:grid-cols-2">@csrf @if($category->exists) @method('put') @endif
        <label>نام دسته‌بندی<input class="field" name="name" required maxlength="100" value="{{ old('name', $category->name) }}"></label>
        <label>ترتیب نمایش<input class="field" name="position" type="number" min="0" value="{{ old('position', $category->position ?: $nextPosition) }}"></label>
        @if($category->exists)<label class="flex min-h-11 items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> فعال و قابل انتخاب</label>@endif
        <div class="sm:col-span-2 flex flex-wrap justify-between gap-3 border-t border-border pt-4"><button class="button-primary">{{ $category->exists ? 'ذخیره تغییرات' : 'افزودن دسته‌بندی' }}</button>@if($category->exists)<button type="submit" form="delete-category" class="button-secondary text-pomegranate">حذف دسته‌بندی</button>@endif</div>
    </form>
    @if($category->exists)<form id="delete-category" method="post" action="{{ route('admin.categories.destroy', $category) }}">@csrf @method('delete')</form>@endif
    @error('name')<p class="field-error mt-3">{{ $message }}</p>@enderror @error('position')<p class="field-error mt-3">{{ $message }}</p>@enderror
@endsection
