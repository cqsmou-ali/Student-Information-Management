<?php

namespace App\Http\Requests\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    // A real bcrypt hash, only used to keep response time equal when the username does not exist.
    private const DUMMY_HASH = '$2y$10$MUY8.oJ7AXRRfAFT4df8je3w3ZYkBBD72gm5DOBj20qSY1aW0Pnca';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * Check the credentials, apply the account lockout, and log the user in.
     * Every failure shows the SAME message so attackers cannot learn which usernames exist.
     */
    public function authenticate(): void
    {
        $username = trim((string) $this->input('username'));
        $user = User::where('username', $username)->first();

        // Always run Hash::check so timing does not reveal whether the user exists.
        $passwordOk = Hash::check((string) $this->input('password'), $user->password_hash ?? self::DUMMY_HASH);

        if (! $user) {
            ActivityLog::record('login_failed', 'Unknown username: ' . Str::limit($username, 50, ''));
            $this->fail();
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            ActivityLog::record('login_blocked', 'Account locked until ' . $user->locked_until, $user->id, $user->role);
            $this->fail();
        }

        if (! $user->is_active) {
            ActivityLog::record('login_blocked', 'Deactivated account', $user->id, $user->role);
            $this->fail();
        }

        if (! $passwordOk) {
            $attempts = $user->failed_attempts + 1;
            $lockedUntil = null;

            if ($attempts >= config('sis.max_failed_attempts')) {
                $lockedUntil = now()->addMinutes(config('sis.lockout_minutes'));
                $attempts = 0;
                ActivityLog::record('account_locked', 'Locked until ' . $lockedUntil, $user->id, $user->role);
            }

            $user->forceFill(['failed_attempts' => $attempts, 'locked_until' => $lockedUntil])->save();
            ActivityLog::record('login_failed', 'Wrong password', $user->id, $user->role);
            $this->fail();
        }

        $user->forceFill(['failed_attempts' => 0, 'locked_until' => null, 'last_login' => now()])->save();
        Auth::login($user, $this->boolean('remember'));
        ActivityLog::record('login_success', 'Logged in', $user->id, $user->role);
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'username' => 'Invalid username or password, or the account is temporarily locked.',
        ]);
    }
}
