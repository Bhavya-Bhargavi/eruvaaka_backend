<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Msg91SmsService
{
    public function sendOtp(string $phone, string $otp): void
    {
        $this->sendSms($phone, str_replace('##OTP##', $otp, (string) config('services.msg91.otp_template', 'Hi test in process ##OTP##')));
    }

    public function sendPaymentConfirmation(string $phone, string $plan, string $paymentId): void
    {
        $this->sendSms($phone, "Eruvaaka payment confirmed for your {$plan} plan. Payment ID: {$paymentId}.");
    }

    public function sendSms(string $phone, string $message): string
    {
        if (app()->environment('testing')) return '';

        $authKey = (string) config('services.msg91.authkey');
        $sender = (string) config('services.msg91.sender');
        if ($authKey === '' || $sender === '') throw new \RuntimeException('MSG91 SMS is not configured');

        $caBundle = config('services.msg91.ca_bundle');
        if (!$caBundle && PHP_OS_FAMILY === 'Windows') {
            $localCaBundle = 'C:/Program Files/Git/usr/ssl/certs/ca-bundle.crt';
            $caBundle = is_file($localCaBundle) ? $localCaBundle : null;
        }

        $request = Http::withOptions(['verify' => $caBundle ?: true]);

        return $request->get('https://api.msg91.com/api/sendhttp.php', [
            'authkey' => $authKey,
            'mobiles' => $this->formatPhone($phone),
            'message' => $message,
            'sender' => $sender,
            'DLT_TE_ID' => config('services.msg91.template_id'),
            'route' => config('services.msg91.route'),
            'country' => config('services.msg91.country'),
        ])->throw()->body();
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', trim($phone));
        $country = (string) config('services.msg91.country', 91);
        return str_starts_with($phone, $country) ? substr($phone, strlen($country)) : $phone;
    }
}