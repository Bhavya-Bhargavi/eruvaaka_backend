<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminPanelNotifier;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['admins' => Admin::latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['adminUser' => new Admin]);
    }

    public function store(Request $request, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('admins', 'email'),
                Rule::unique('admins', 'pending_email')->where(
                    fn (Builder $query) => $query->where('email_change_expires_at', '>', now()),
                ),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(['super_admin', 'content_manager'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        $data['is_active'] = $request->boolean('is_active');

        $adminUser = Admin::create($data);
        $notifications->notifyChange(
            $request,
            'created',
            'Admin account',
            "{$adminUser->name} ({$adminUser->email})",
            [$adminUser->email],
        );

        return redirect()->route('admin.users.index')->with('status', 'Admin account created.');
    }

    public function edit(Admin $adminUser): View
    {
        return view('admin.users.form', ['adminUser' => $adminUser]);
    }

    public function update(Request $request, Admin $adminUser, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($adminUser->id),
                Rule::unique('admins', 'pending_email')
                    ->where(fn (Builder $query) => $query->where('email_change_expires_at', '>', now()))
                    ->ignore($adminUser->id),
            ],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(['super_admin', 'content_manager'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['email'] = strtolower(trim($data['email']));
        $data['is_active'] = $request->boolean('is_active');

        if ($adminUser->is(Auth::guard('admin')->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.'])->withInput();
        }

        if ($adminUser->role === 'super_admin'
            && ($data['role'] !== 'super_admin' || ! $data['is_active'])
            && Admin::where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['role' => 'At least one active Super Admin account must remain.'])->withInput();
        }

        $requestedEmail = strtolower(trim($data['email']));
        $emailChanged = $requestedEmail !== strtolower($adminUser->email);
        $passwordChanged = ! empty($data['password']);
        $changed = [];

        foreach (['name' => 'name', 'role' => 'role', 'is_active' => 'account status'] as $field => $label) {
            if ($adminUser->getAttribute($field) !== $data[$field]) {
                $changed[] = $label;
            }
        }
        if ($passwordChanged) {
            $changed[] = 'password';
        }
        if ($emailChanged) {
            $changed[] = 'email address';
        }

        unset($data['email']);
        if (! $passwordChanged) {
            unset($data['password']);
        }

        $adminUser->fill($data);
        $verificationUrl = null;
        if ($emailChanged) {
            $rawToken = Str::random(64);
            $expiresAt = now()->addHours(24);
            $adminUser->forceFill([
                'pending_email' => $requestedEmail,
                'email_change_token' => hash('sha256', $rawToken),
                'email_change_expires_at' => $expiresAt,
            ]);
            $verificationUrl = URL::temporarySignedRoute('admin.email.verify', $expiresAt, [
                'adminUser' => $adminUser->getKey(),
                'token' => $rawToken,
            ]);
        }
        $adminUser->save();

        if ($verificationUrl !== null) {
            $notifications->sendEmailChangeVerification(
                $request,
                $requestedEmail,
                $adminUser->name,
                $verificationUrl,
            );
        }
        if ($changed !== []) {
            $action = 'updated ('.implode(', ', $changed).')';
            if ($emailChanged) {
                $action .= '; email change pending verification';
            }
            $notifications->notifyChange(
                $request,
                $action,
                'Admin account',
                "{$adminUser->name} ({$adminUser->email})",
                [$adminUser->email],
            );
        }

        return redirect()->route('admin.users.index')->with('status', 'Admin account updated.');
    }

    public function destroy(Request $request, Admin $adminUser, AdminPanelNotifier $notifications): RedirectResponse
    {
        if ($adminUser->is(Auth::guard('admin')->user())) {
            return back()->withErrors(['admin' => 'You cannot delete your own account.']);
        }

        if ($adminUser->role === 'super_admin'
            && $adminUser->is_active
            && Admin::where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['admin' => 'At least one active Super Admin account must remain.']);
        }

        $account = "{$adminUser->name} ({$adminUser->email})";
        $email = $adminUser->email;
        $adminUser->delete();
        $notifications->notifyChange($request, 'deleted', 'Admin account', $account, [$email]);

        return redirect()->route('admin.users.index')->with('status', 'Admin account deleted.');
    }
}
