@extends('layouts.auth')
@section('title', 'نشست شما منقضی شده است')
@section('content')
<h1 class="text-2xl font-bold">نشست شما منقضی شده است</h1>
<p class="mt-4 text-sm leading-7 text-secondary">صفحه را دوباره باز کنید و برای ادامه وارد شوید.</p>
<a href="{{ route('home') }}" class="mt-6 flex min-h-12 items-center justify-center rounded-xl bg-pomegranate px-5 font-semibold text-white">بازگشت به خانه</a>
@endsection
