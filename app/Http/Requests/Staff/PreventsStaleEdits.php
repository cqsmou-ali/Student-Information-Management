<?php

namespace App\Http\Requests\Staff;

use Illuminate\Validation\Validator;

/**
 * Optimistic locking for the edit forms. The form carries the record's version (Student::editVersion())
 * from when it was opened. If someone saved the record since then, the save is refused instead of
 * silently overwriting their changes.
 */
trait PreventsStaleEdits
{
    public const STALE_MESSAGE = 'Another user changed this record after you opened it, so your changes were not saved.';

    protected function checkNotStale(Validator $validator): void
    {
        $sent = $this->input('loaded_at');
        $current = $this->route('student')->editVersion();

        if (! is_string($sent) || ! hash_equals($current, $sent)) {
            $validator->errors()->add('loaded_at', self::STALE_MESSAGE);
        }
    }
}
