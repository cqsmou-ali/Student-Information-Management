<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/** Second step of "Forgot password": the page opened from the emailed link. */
class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(config('sis.min_password_length'))->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                // Our password column is password_hash (hashed by the model cast).
                $user->forceFill([
                    'password_hash'        => $request->password,
                    'must_change_password' => false,
                    'failed_attempts'      => 0,
                    'locked_until'         => null,
                    'remember_token'       => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                ActivityLog::record('password_reset', 'Password reset using an emailed link', $user->id, $user->role);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password was reset. You can now log in.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
    }
}
