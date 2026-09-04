@extends('layouts.auth')
@section('title', 'مشکلی پیش آمده است')
@section('content')
<h1 class="text-2xl font-bold">مشکلی پیش آمده است</h1>
<p class="mt-4 text-sm leading-7 text-secondary">در حال حاضر امکان پردازش درخواست شما وجود ندارد. کمی بعد دوباره تلاش کنید.</p>
<a href="{{ route('home') }}" class="mt-6 flex min-h-12 items-center justify-center rounded-xl bg-pomegranate px-5 font-semibold text-white">بازگشت به خانه</a>
@endsection
