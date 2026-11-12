<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Students with a temporary password must set a new one before using anything else. */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
