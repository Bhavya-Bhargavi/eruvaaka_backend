<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminPanelNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailChangeVerificationController extends Controller
{
    public function __invoke(
        Request $request,
        Admin $adminUser,
        string $token,
        AdminPanelNotifier $notifications,
    ): RedirectResponse {
        if (
            ! $adminUser->pending_email
            || ! $adminUser->email_change_token
            || ! $adminUser->email_change_expires_at
            || $adminUser->email_change_expires_at->isPast()
            || ! hash_equals($adminUser->email_change_token, hash('sha256', $token))
        ) {
            abort(403, 'This email verification link is invalid or has expired.');
        }

        $newEmail = $adminUser->pending_email;
        $conflict = Admin::query()
            ->where('id', '<>', $adminUser->id)
            ->where(function ($query) use ($newEmail): void {
                $query->where('email', $newEmail)->orWhere(function ($query) use ($newEmail): void {
                    $query->where('pending_email', $newEmail)
                        ->where('email_change_expires_at', '>', now());
                });
            })
            ->exists();

        if ($conflict) {
            $adminUser->forceFill([
                'pending_email' => null,
                'email_change_token' => null,
                'email_change_expires_at' => null,
            ])->save();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'This email address is no longer available. Ask a Super Admin to submit a new address.']);
        }

        $oldEmail = $adminUser->email;
        $adminUser->forceFill([
            'email' => $newEmail,
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_expires_at' => null,
        ])->save();

        $notifications->notifyChange(
            $request,
            'email address verified',
            'Admin account',
            $adminUser->name,
            [$oldEmail, $newEmail],
        );

        return redirect()->route('admin.login')
            ->with('status', 'Your admin email address has been updated. You can now sign in with the new address.');
    }
}
