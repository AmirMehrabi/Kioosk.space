@extends('layouts.auth')
@section('title', 'کمی صبر کنید')
@section('content')
<h1 class="text-2xl font-bold">کمی صبر کنید</h1>
<p class="mt-4 text-sm leading-7 text-secondary">تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.</p>
<a href="{{ route('home') }}" class="mt-6 flex min-h-12 items-center justify-center rounded-xl bg-pomegranate px-5 font-semibold text-white">بازگشت به خانه</a>
@endsection
