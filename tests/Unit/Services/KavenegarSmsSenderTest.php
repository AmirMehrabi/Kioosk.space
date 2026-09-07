<?php

namespace Tests\Unit\Services;

use App\Exceptions\KavenegarSmsException;
use App\Services\KavenegarApiFactory;
use App\Services\KavenegarSmsSender;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\KavenegarApi;
use Mockery;
use Tests\TestCase;

class KavenegarSmsSenderTest extends TestCase
{
    public function test_lookup_sends_only_the_otp_token_to_the_configured_template(): void
    {
        config()->set([
            'services.kavenegar.api_key' => 'test-api-key',
            'services.kavenegar.template' => 'login-code',
        ]);
        $api = Mockery::mock(KavenegarApi::class);
        $api->shouldReceive('VerifyLookup')->once()->with('09123456789', '01234', null, null, 'login-code')->andReturn([(object) ['messageid' => 123]]);
        $this->mock(KavenegarApiFactory::class)->shouldReceive('create')->once()->with('test-api-key')->andReturn($api);

        app(KavenegarSmsSender::class)->send('+989123456789', '01234');
    }

    public function test_lookup_rejects_an_unsuccessful_api_result(): void
    {
        config()->set([
            'services.kavenegar.api_key' => 'test-api-key',
            'services.kavenegar.template' => 'login-code',
        ]);
        $api = Mockery::mock(KavenegarApi::class);
        $api->shouldReceive('VerifyLookup')->once()->andThrow(new ApiException('ساختار کد صحیح نمی باشد', 431));
        $this->mock(KavenegarApiFactory::class)->shouldReceive('create')->once()->with('test-api-key')->andReturn($api);

        try {
            app(KavenegarSmsSender::class)->send('+989123456789', '01234');
            $this->fail('An unsuccessful Kavenegar result was accepted.');
        } catch (KavenegarSmsException $exception) {
            $this->assertSame('Kavenegar rejected the OTP delivery request.', $exception->getMessage());
            $this->assertSame(431, $exception->providerStatus());
        }
    }

    public function test_lookup_does_not_send_without_required_configuration(): void
    {
        config()->set([
            'services.kavenegar.api_key' => null,
            'services.kavenegar.template' => null,
        ]);
        $this->mock(KavenegarApiFactory::class)->shouldNotReceive('create');

        try {
            app(KavenegarSmsSender::class)->send('+989123456789', '01234');
            $this->fail('Missing Kavenegar configuration was accepted.');
        } catch (KavenegarSmsException $exception) {
            $this->assertSame('Kavenegar OTP delivery is not configured.', $exception->getMessage());
            $this->assertNull($exception->providerStatus());
        }
    }
}
