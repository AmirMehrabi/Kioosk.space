<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class KavenegarSmsSender
{
    public function send(string $mobile, #[\SensitiveParameter] string $token): void
    {
        $apiKey = config('services.kavenegar.api_key');
        $template = config('services.kavenegar.template');
        $baseUrl = config('services.kavenegar.base_url');

        if (! is_string($apiKey) || trim($apiKey) === '' || ! is_string($template) || trim($template) === '' || ! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new RuntimeException('Kavenegar OTP delivery is not configured.');
        }

        $response = Http::acceptJson()
            ->asForm()
            ->connectTimeout((int) config('services.kavenegar.connect_timeout'))
            ->timeout((int) config('services.kavenegar.timeout'))
            ->post(rtrim($baseUrl, '/').'/v1/'.rawurlencode($apiKey).'/verify/lookup.json', [
                'receptor' => preg_replace('/^\+98/', '0', $mobile),
                'token' => $token,
                'template' => $template,
            ])
            ->throw();

        if ((int) $response->json('return.status') !== 200) {
            throw new RuntimeException('Kavenegar rejected the OTP delivery request.');
        }
    }
}
