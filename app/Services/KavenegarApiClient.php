<?php

namespace App\Services;

use Kavenegar\KavenegarApi;

class KavenegarApiClient extends KavenegarApi
{
    protected string $apiKey;

    protected bool $insecure;
}
