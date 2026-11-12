<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/** "Forgot password": emails a reset link to the address on the account. */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:120']]);

        $user = User::where('email', trim($request->input('email')))->where('is_active', true)->first();

        if ($user) {
            $status = Password::sendResetLink(['email' => $user->email]);
            ActivityLog::record(
                $status === Password::RESET_LINK_SENT ? 'password_reset_requested' : 'password_reset_throttled',
                'Password reset link requested', $user->id, $user->role
            );
        } else {
            ActivityLog::record('password_reset_unknown', 'Reset requested for an unknown or inactive email');
        }

        // Same message either way, so nobody can use this form to find out which emails have accounts.
        return back()->with('status', 'If that email belongs to an active account, a password reset link has been sent. Please check your inbox (and spam folder). The link expires in 60 minutes.');
    }
}
