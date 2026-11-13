<?php

namespace App\Services;

use App\Models\Admin;
use App\Notifications\AdminEmailChangeVerificationNotification;
use App\Notifications\AdminPanelChangeNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AdminPanelNotifier
{
    public function notifyChange(
        Request $request,
        string $action,
        string $recordType,
        string $recordName,
        array $additionalRecipients = [],
        ?string $performedBy = null,
    ): void {
        $actor = $request->user('admin');
        $performedBy ??= $actor ? "{$actor->name} ({$actor->email})" : 'Email address verification';
        $recipients = [
            $actor?->email,
            ...Admin::query()->where('role', 'super_admin')->where('is_active', true)->pluck('email')->all(),
            config('admin.notification_email'),
            ...$additionalRecipients,
        ];

        $this->sendTo($request, $recipients, new AdminPanelChangeNotification(
            $action,
            $recordType,
            $recordName,
            $performedBy,
        ));
    }

    public function sendEmailChangeVerification(
        Request $request,
        string $email,
        string $adminName,
        string $verificationUrl,
    ): void {
        $this->sendTo($request, [$email], new AdminEmailChangeVerificationNotification($adminName, $verificationUrl));
    }

    private function sendTo(Request $request, array $recipients, BaseNotification $notification): void
    {
        $emails = collect($recipients)
            ->filter(fn ($email): bool => is_string($email) && trim($email) !== '')
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->unique()
            ->values();
        $failures = [];

        foreach ($emails as $email) {
            try {
                Notification::route('mail', $email)->notify(clone $notification);
            } catch (Throwable $exception) {
                report($exception);
                $failures[] = $email;
            }
        }

        $warnings = [];
        if (config('mail.default') === 'log') {
            $warnings[] = 'Mail is in log mode, so notifications are written to the application log instead of being delivered.';
        }
        if ($failures !== []) {
            $warnings[] = 'The change was saved, but one or more email notifications could not be sent. Check the mail configuration and application logs.';
        }
        if ($warnings !== []) {
            $request->session()->flash('notification_warning', implode(' ', $warnings));
        }
    }
}
