@extends('layouts.community')
@section('title', $category->name)
@section('metaDescription', 'کسب‌وکارهای دسته '.$category->name.' همراه با امتیاز و تجربه کاربران کیوسک.')
@section('canonical', route('categories.show', $category->slug))
@section('breadcrumbs')<x-breadcrumbs :items="[['label' => $category->name]]" />@endsection
@section('content')
<header class="mb-8"><h1 class="text-3xl font-extrabold">{{ $category->name }}</h1><p class="mt-3 text-secondary">مکان‌های این دسته بر پایه تجربه‌های منتشرشده کاربران</p></header>
@include('entities.place-list', ['businesses' => $businesses])
@endsection
