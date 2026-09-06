<?php

namespace Tests\Unit\Services;

use App\Services\KavenegarSmsSender;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class KavenegarSmsSenderTest extends TestCase
{
    public function test_lookup_sends_only_the_otp_token_to_the_configured_template(): void
    {
        config()->set([
            'services.kavenegar.api_key' => 'test-api-key',
            'services.kavenegar.template' => 'login-code',
            'services.kavenegar.base_url' => 'https://api.kavenegar.com',
            'services.kavenegar.connect_timeout' => 2,
            'services.kavenegar.timeout' => 4,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.kavenegar.com/v1/test-api-key/verify/lookup.json' => Http::response([
                'return' => ['status' => 200, 'message' => 'تایید شد'],
                'entries' => [['messageid' => 123]],
            ]),
        ]);

        app(KavenegarSmsSender::class)->send('+989123456789', '01234');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.kavenegar.com/v1/test-api-key/verify/lookup.json'
            && $request->method() === 'POST'
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $request->data() === [
                'receptor' => '09123456789',
                'token' => '01234',
                'template' => 'login-code',
            ]);
    }

    public function test_lookup_rejects_an_unsuccessful_api_result(): void
    {
        config()->set([
            'services.kavenegar.api_key' => 'test-api-key',
            'services.kavenegar.template' => 'login-code',
            'services.kavenegar.base_url' => 'https://api.kavenegar.com',
            'services.kavenegar.connect_timeout' => 2,
            'services.kavenegar.timeout' => 4,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.kavenegar.com/v1/test-api-key/verify/lookup.json' => Http::response([
                'return' => ['status' => 431, 'message' => 'ساختار کد صحیح نمی باشد'],
                'entries' => null,
            ]),
        ]);

        $this->expectException(RuntimeException::class);

        app(KavenegarSmsSender::class)->send('+989123456789', '01234');
    }

    public function test_lookup_does_not_send_without_required_configuration(): void
    {
        config()->set([
            'services.kavenegar.api_key' => null,
            'services.kavenegar.template' => null,
            'services.kavenegar.base_url' => 'https://api.kavenegar.com',
        ]);
        Http::preventStrayRequests();

        try {
            app(KavenegarSmsSender::class)->send('+989123456789', '01234');
            $this->fail('Missing Kavenegar configuration was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Kavenegar OTP delivery is not configured.', $exception->getMessage());
        }

        Http::assertNothingSent();
    }
}
