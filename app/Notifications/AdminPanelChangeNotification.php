<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPanelChangeNotification extends Notification
{
    public function __construct(
        private readonly string $action,
        private readonly string $recordType,
        private readonly string $recordName,
        private readonly string $performedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Eruvaaka Admin: {$this->recordType} {$this->action}")
            ->greeting('Admin panel change')
            ->line("Action: {$this->action}")
            ->line("Record: {$this->recordType} - {$this->recordName}")
            ->line("Performed by: {$this->performedBy}")
            ->line('Time: '.now()->toDateTimeString())
            ->line('This alert does not contain passwords or uploaded files.');
    }
}
