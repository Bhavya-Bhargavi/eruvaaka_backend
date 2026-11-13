<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminEmailChangeVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $adminName,
        public readonly string $verificationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your Eruvaaka Admin email address')
            ->greeting("Hello {$this->adminName},")
            ->line('A Super Admin requested to change the email address for your admin account.')
            ->action('Verify email address', $this->verificationUrl)
            ->line('This link expires in 24 hours. Your current email remains active until you verify the new address.')
            ->line('If you did not expect this request, contact a Super Admin. No password is included in this email.');
    }
}
