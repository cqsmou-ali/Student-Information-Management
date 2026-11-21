<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'role', 'action', 'details', 'ip_address', 'subject_type', 'subject_id', 'field_changes'];

    protected function casts(): array
    {
        return ['field_changes' => 'array'];
    }

    /**
     * The log is append-only: an entry can be written, never edited or deleted through the app.
     * Returning false from these events cancels the save / delete.
     */
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    /** The record this entry is about (for example a Student). */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Write an audit entry. Logging must never break the page,
     * so any failure is sent to the Laravel log instead.
     *
     * @param  Model|null  $subject  the record acted on, so "everything that happened to X" is one query
     * @param  array  $changes  ['field' => ['from' => old, 'to' => new]], see diff()
     */
    public static function record(string $action, string $details = '', ?int $userId = null, ?string $role = null, ?Model $subject = null, array $changes = []): void
    {
        try {
            $user = Auth::user();
            self::create([
                'user_id'       => $userId ?? $user?->id,
                'role'          => $role ?? $user?->role,
                'action'        => $action,
                'details'       => mb_substr($details, 0, 1000),
                'ip_address'    => request()->ip(),
                'subject_type'  => $subject?->getMorphClass(),
                'subject_id'    => $subject?->getKey(),
                'field_changes' => $changes ?: null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Old and new value of every field changed on $model but not saved yet. Call it before save(). */
    public static function diff(Model $model): array
    {
        $plain = fn ($v) => $v instanceof DateTimeInterface ? $v->format('Y-m-d') : $v;

        $out = [];
        foreach (array_keys($model->getDirty()) as $field) {
            if ($field === 'updated_at') {
                continue;
            }
            $out[$field] = ['from' => $plain($model->getOriginal($field)), 'to' => $plain($model->getAttribute($field))];
        }

        return $out;
    }
}
