<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;

/** Role-based access control. Usage: ->middleware('role:admin') or 'role:admin,staff'. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            ActivityLog::record('access_denied', 'Tried to open /' . $request->path());
            abort(403);
        }

        return $next($request);
    }
}
