<?php

namespace App\Services;

use App\Exceptions\KavenegarSmsException;
use Kavenegar\Exceptions\ApiException;

class KavenegarSmsSender
{
    public function __construct(private KavenegarApiFactory $factory) {}

    public function send(string $mobile, #[\SensitiveParameter] string $token): void
    {
        $apiKey = config('services.kavenegar.api_key');
        $template = config('services.kavenegar.template');

        if (! is_string($apiKey) || trim($apiKey) === '' || ! is_string($template) || trim($template) === '') {
            throw new KavenegarSmsException('Kavenegar OTP delivery is not configured.');
        }

        try {
            $this->factory->create(trim($apiKey))->VerifyLookup(
                preg_replace('/^\+98/', '0', $mobile),
                $token,
                null,
                null,
                trim($template),
            );
        } catch (ApiException $exception) {
            throw new KavenegarSmsException(
                'Kavenegar rejected the OTP delivery request.',
                $exception->getCode(),
            );
        }
    }
}
