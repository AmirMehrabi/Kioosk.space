<?php

use App\Models\OtpChallenge;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune', ['--model' => [OtpChallenge::class]])->daily()->timezone('Asia/Tehran');

Schedule::command('contributions:cleanup')->dailyAt('03:00')->timezone('Asia/Tehran')->withoutOverlapping();
Schedule::command('queue:monitor database:default --max=100')->everyFiveMinutes();
