<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminPanelNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.account.password');
    }

    public function update(Request $request, AdminPanelNotifier $notifications): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'different:current_password'],
        ]);
        $admin = $request->user('admin');

        if (! Hash::check($data['current_password'], $admin->password)) {
            return redirect()->route('admin.password.edit')
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->withInput($request->except(['current_password', 'password', 'password_confirmation']));
        }

        $admin->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();
        $request->session()->regenerate();
        $notifications->notifyChange($request, 'changed', 'Admin password', $admin->name, [$admin->email]);

        return redirect()->route('admin.password.edit')->with('status', 'Your password has been updated.');
    }
}
