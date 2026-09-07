<?php

namespace Tests\Unit\Services;

use App\Services\KavenegarApiClient;
use App\Services\KavenegarApiFactory;
use Kavenegar\KavenegarApi;
use Tests\TestCase;

class KavenegarApiFactoryTest extends TestCase
{
    public function test_creates_a_php_85_compatible_official_api_client(): void
    {
        $client = app(KavenegarApiFactory::class)->create('test-api-key');

        $this->assertInstanceOf(KavenegarApi::class, $client);
        $this->assertSame(KavenegarApiClient::class, $client::class);
    }
}
