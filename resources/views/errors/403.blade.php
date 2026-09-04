@extends('layouts.auth')
@section('title', 'دسترسی مجاز نیست')
@section('content')
<h1 class="text-2xl font-bold">دسترسی مجاز نیست</h1>
<p class="mt-4 text-sm leading-7 text-secondary">حساب شما اجازه ورود به این بخش را ندارد.</p>
<a href="{{ route('home') }}" class="mt-6 flex min-h-12 items-center justify-center rounded-xl bg-pomegranate px-5 font-semibold text-white">بازگشت به خانه</a>
@endsection
