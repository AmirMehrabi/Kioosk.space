@extends('layouts.community')
@section('title', 'کسب‌وکارهای '.$city->name)
@section('metaDescription', 'کشف کسب‌وکارهای '.$city->name.' همراه با امتیاز، آدرس و تجربه کاربران کیوسک.')
@section('canonical', route('cities.show', $city->slug))
@section('breadcrumbs')<x-breadcrumbs :items="[['label' => $city->name]]" />@endsection
@section('content')
<header class="mb-8"><h1 class="text-3xl font-extrabold">کسب‌وکارهای {{ $city->name }}</h1><p class="mt-3 text-secondary">مکان‌ها و تجربه‌های واقعی مردم در {{ $city->name }}</p></header>
@include('entities.place-list', ['businesses' => $businesses])
@endsection
