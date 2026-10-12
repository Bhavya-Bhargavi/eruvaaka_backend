<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InfinitoSmsService
{
    public function sendOtp(string $phone, string $otp): void
    {
        $template = (string) config('services.infinito_sms.otp_template', '');
        $templateId = (string) config('services.infinito_sms.otp_dlt_template_id', '');

        if (!str_contains($template, '{otp}')) {
            throw new \InvalidArgumentException('Infinito OTP template must contain the {otp} placeholder.');
        }

        $this->sendMessage($phone, str_replace('{otp}', $otp, $template), $templateId);
    }

    public function sendPaymentConfirmation(string $phone, string $planType, string $paymentId): void
    {
        $template = (string) config('services.infinito_sms.payment_template', '');
        $templateId = (string) config('services.infinito_sms.payment_dlt_template_id', '');

        if (!str_contains($template, '{plan}') || !str_contains($template, '{payment_id}')) {
            throw new \InvalidArgumentException('Infinito payment template must contain {plan} and {payment_id} placeholders.');
        }

        $text = str_replace(['{plan}', '{payment_id}'], [$planType, $paymentId], $template);
        $this->sendMessage($phone, $text, $templateId);
    }

    private function sendMessage(string $phone, string $text, string $templateId): void
    {
        $endpoint = trim((string) config('services.infinito_sms.endpoint', ''));
        $apiToken = trim((string) config('services.infinito_sms.api_token', ''));
        $sender = (string) config('services.infinito_sms.from', '');

        if ($endpoint === '' || $apiToken === '' || $sender === '' || $templateId === '') {
            throw new \InvalidArgumentException('Infinito SMS is not configured.');
        }

        $to = $this->destinationNumber($phone);
        $request = Http::timeout((int) config('services.infinito_sms.timeout', 10))
            ->withToken($apiToken)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        $caBundle = trim((string) config('services.infinito_sms.ca_bundle', ''));
        if ($caBundle !== '') {
            if (!is_file($caBundle) || !is_readable($caBundle)) {
                throw new \InvalidArgumentException('The configured Infinito CA bundle is not readable.');
            }
            $request->withOptions(['verify' => $caBundle]);
        }

        $response = $request->post($endpoint, [
            'apiver' => '1.0',
            'sms' => [
                'ver' => '2.0',
                'dlr' => ['url' => ''],
                'messages' => [[
                    'udh' => '0',
                    'dlttemplateid' => $templateId,
                    'dltcontenttype' => (int) config('services.infinito_sms.dlt_content_type', 1),
                    'coding' => 1,
                    'text' => $text,
                    'property' => (int) config('services.infinito_sms.property', 0),
                    'id' => '1',
                    'addresses' => [[
                        'seq' => 'hardiktest',
                        'to' => $to,
                        'from' => $sender,
                        'tag' => 'Clients optional information',
                    ]],
                ]],
            ],
        ]);

        $response->throw();

        $body = $response->json();
        if (($body['status'] ?? null) !== 'Success' || (int) ($body['statuscode'] ?? 0) !== 200) {
            throw new \UnexpectedValueException('Infinito did not accept the SMS request.');
        }

        foreach (($body['messageack']['guids'] ?? []) as $guid) {
            $errors = $guid['errors'] ?? [];
            if ($errors !== []) {
                $error = $errors[0] ?? [];
                $code = $error['errorcode'] ?? 'unknown';
                $message = $error['errortext'] ?? 'Unknown provider error.';
                throw new \UnexpectedValueException("Infinito SMS error {$code}: {$message}");
            }
        }
    }

    private function destinationNumber(string $phone): string
    {
        $number = preg_replace('/\D+/', '', $phone) ?? '';
        $prefix = preg_replace('/\D+/', '', (string) config('services.infinito_sms.country_prefix', '91')) ?? '';

        if ($prefix !== '' && !str_starts_with($number, $prefix)) {
            return $prefix . $number;
        }

        return $number;
    }
}
