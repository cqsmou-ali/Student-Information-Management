<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'role', 'action', 'details', 'ip_address'];

    /**
     * Write an audit entry. Logging must never break the page,
     * so any failure is sent to the Laravel log instead.
     */
    public static function record(string $action, string $details = '', ?int $userId = null, ?string $role = null): void
    {
        try {
            $user = Auth::user();
            self::create([
                'user_id'    => $userId ?? $user?->id,
                'role'       => $role ?? $user?->role,
                'action'     => $action,
                'details'    => mb_substr($details, 0, 1000),
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
