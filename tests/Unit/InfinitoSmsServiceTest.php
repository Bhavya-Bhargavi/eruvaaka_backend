<?php

namespace Tests\Unit;

use App\Services\InfinitoSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class InfinitoSmsServiceTest extends TestCase
{
    public function test_sends_otp_using_the_infinito_sms_json_payload(): void
    {
        config([
            'services.infinito_sms.endpoint' => 'https://sms.example.test/unified/v2/send',
            'services.infinito_sms.api_token' => 'test-api-token',
            'services.infinito_sms.from' => 'ERUVKA',
            'services.infinito_sms.otp_dlt_template_id' => '1777179143324399534',
            'services.infinito_sms.otp_template' => 'Your OTP for logging in to the Eruvaaka App is {otp}. If you did not request this OTP, please contact our support team." Eruvaaka Publications.',
            'services.infinito_sms.dlt_content_type' => 1,
            'services.infinito_sms.country_prefix' => '91',
        ]);
        Http::fake(['*' => Http::response([
            'status' => 'Success',
            'statuscode' => 200,
            'statustext' => 'OK',
            'messageack' => ['guids' => [['guid' => 'test-guid', 'errors' => []]]],
        ], 200)]);

        app(InfinitoSmsService::class)->sendOtp('9876543210', '123456');

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://sms.example.test/unified/v2/send'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-api-token')
                && !$request->hasHeader('x-api-token')
                && !$request->hasHeader('x-client-id')
                && !$request->hasHeader('x-client-password')
                && str_starts_with((string) $request->header('Content-Type')[0], 'application/json')
                && array_keys($body) === ['apiver', 'sms']
                && ($body['apiver'] ?? null) === '1.0'
                && ($body['sms']['ver'] ?? null) === '2.0'
                && ($body['sms']['messages'][0]['udh'] ?? null) === '0'
                && ($body['sms']['messages'][0]['dlttemplateid'] ?? null) === '1777179143324399534'
                && ($body['sms']['messages'][0]['dltcontenttype'] ?? null) === 1
                && ($body['sms']['messages'][0]['coding'] ?? null) === 1
                && ($body['sms']['messages'][0]['text'] ?? null) === 'Your OTP for logging in to the Eruvaaka App is 123456. If you did not request this OTP, please contact our support team." Eruvaaka Publications.'
                && ($body['sms']['messages'][0]['property'] ?? null) === 0
                && ($body['sms']['messages'][0]['id'] ?? null) === '1'
                && ($body['sms']['messages'][0]['addresses'][0]['from'] ?? null) === 'ERUVKA'
                && ($body['sms']['messages'][0]['addresses'][0]['to'] ?? null) === '919876543210';
        });
    }

    public function test_throws_when_infinito_rejects_the_request(): void
    {
        config([
            'services.infinito_sms.endpoint' => 'https://sms.example.test/unified/',
            'services.infinito_sms.api_token' => 'test-api-token',
            'services.infinito_sms.from' => 'LDSPLD',
            'services.infinito_sms.otp_dlt_template_id' => 'otp-template-id',
            'services.infinito_sms.otp_template' => 'Your Eruvaaka OTP is {otp}.',
        ]);
        Http::fake(['*' => Http::response('Rejected', 500)]);
        $this->expectException(RequestException::class);

        app(InfinitoSmsService::class)->sendOtp('9876543210', '123456');
    }

    public function test_throws_when_provider_returns_http_200_with_sender_error(): void
    {
        config([
            'services.infinito_sms.endpoint' => 'https://sms.example.test/unified/v2/send',
            'services.infinito_sms.api_token' => 'test-api-token',
            'services.infinito_sms.from' => 'LDSPLD',
            'services.infinito_sms.otp_dlt_template_id' => 'otp-template-id',
            'services.infinito_sms.otp_template' => 'Your Eruvaaka OTP is {otp}.',
        ]);
        Http::fake(['*' => Http::response([
            'status' => 'Success',
            'statuscode' => 200,
            'messageack' => ['guids' => [[
                'errors' => [[
                    'errorcode' => 28680,
                    'errortext' => 'Invalid Sender ID',
                ]],
            ]]],
        ], 200)]);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Infinito SMS error 28680: Invalid Sender ID');

        app(InfinitoSmsService::class)->sendOtp('9876543210', '123456');
    }

    public function test_throws_when_infinito_credentials_are_missing(): void
    {
        config([
            'services.infinito_sms.endpoint' => '',
            'services.infinito_sms.api_token' => '',
            'services.infinito_sms.from' => '',
            'services.infinito_sms.otp_dlt_template_id' => 'otp-template-id',
            'services.infinito_sms.otp_template' => 'Your Eruvaaka OTP is {otp}.',
        ]);
        $this->expectException(InvalidArgumentException::class);

        app(InfinitoSmsService::class)->sendOtp('9876543210', '123456');
    }
}