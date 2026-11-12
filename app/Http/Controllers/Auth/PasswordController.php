<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Change password. Also the forced first-login page for new student accounts. */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', ['forced' => $request->user()->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required', 'string', 'confirmed', 'different:current_password',
                Password::min(config('sis.min_password_length'))->mixedCase()->numbers(),
            ],
        ]);

        if (! Hash::check($validated['current_password'], $user->password_hash)) {
            ActivityLog::record('password_change_failed', 'Wrong current password');
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $user->forceFill(['password_hash' => $validated['password'], 'must_change_password' => false])->save();
        $request->session()->regenerate();
        ActivityLog::record('password_changed', 'Password changed');

        return redirect()->route('dashboard')->with('status', 'Your password was updated.');
    }
}
