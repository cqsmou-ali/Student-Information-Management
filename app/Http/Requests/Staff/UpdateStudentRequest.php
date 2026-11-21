<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

/**
 * Registrar: update a student's personal information. The student number is never accepted here.
 * Identity fields (name, birthdate, sex) can only be changed with a written reason, which goes into the audit log.
 */
class UpdateStudentRequest extends FormRequest
{
    use PreventsStaleEdits;

    /** Fields that identify the student; changing any of them needs a reason. */
    public const SENSITIVE = ['first_name' => 'first name', 'middle_name' => 'middle name', 'last_name' => 'last name', 'birthdate' => 'birthdate', 'sex' => 'sex'];

    public function after(): array
    {
        return [
            fn ($validator) => $this->checkNotStale($validator),
            fn ($validator) => $this->checkReason($validator),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'        => StoreStudentRequest::NAME,
            'middle_name'       => StoreStudentRequest::MIDDLE_NAME,
            'last_name'         => StoreStudentRequest::NAME,
            'birthdate'         => ['required', 'date', 'before:today', 'after:1930-01-01'],
            'sex'               => ['required', Rule::in(['Male', 'Female'])],
            'email'             => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($this->route('student')->user_id)],
            'contact_number'    => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address'           => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
            'change_reason'     => ['nullable', 'string', 'min:5', 'max:255'],
        ];
    }

    /** Which identity fields differ from what is saved now (only meaningful once those fields are valid). */
    public function changedSensitiveFields(): array
    {
        $student = $this->route('student');
        $changed = [];

        foreach (['first_name', 'middle_name', 'last_name', 'sex'] as $field) {
            if ((string) $this->input($field) !== (string) $student->{$field}) {
                $changed[] = $field;
            }
        }
        try {
            if (Carbon::parse((string) $this->input('birthdate'))->format('Y-m-d') !== $student->birthdate?->format('Y-m-d')) {
                $changed[] = 'birthdate';
            }
        } catch (Throwable) {
            // an unparseable date already fails the birthdate rule
        }

        return $changed;
    }

    protected function checkReason(Validator $validator): void
    {
        $errors = $validator->errors();
        if ($this->filled('change_reason') || $errors->hasAny(array_keys(self::SENSITIVE))) {
            return;
        }

        $changed = $this->changedSensitiveFields();
        if ($changed !== []) {
            $labels = implode(', ', array_map(fn ($f) => self::SENSITIVE[$f], $changed));
            $errors->add('change_reason', "Give a reason for this change (you changed: {$labels}).");
        }
    }
}
