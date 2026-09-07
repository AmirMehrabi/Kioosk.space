<?php

namespace App\Services;

use Kavenegar\KavenegarApi;

class KavenegarApiFactory
{
    public function create(string $apiKey): KavenegarApi
    {
        return new KavenegarApiClient($apiKey);
    }
}
