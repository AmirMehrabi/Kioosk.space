<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LogSmsSender
{
    public function send(string $mobile, #[\SensitiveParameter] string $code): void
    {
        Log::channel(config('otp.log_channel'))->info('پیامک ورود کیوسک', [
            'to' => $mobile,
            'message' => "کد ورود شما به کیوسک: {$code}\nاعتبار: ۳ دقیقه. این کد را در اختیار دیگران قرار ندهید.",
        ]);
    }
}
