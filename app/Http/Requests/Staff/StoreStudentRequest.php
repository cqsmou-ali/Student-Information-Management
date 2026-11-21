<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Registrar: register a new student (personal + enrollment information). Access is checked by role:staff. */
class StoreStudentRequest extends FormRequest
{
    use ChecksEnrollment;

    public const NAME = ['required', 'string', 'max:60', "regex:/^[\\pL\\s.'-]+$/u"];
    public const MIDDLE_NAME = ['nullable', 'string', 'max:60', "regex:/^[\\pL\\s.'-]+$/u"];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number'    => ['required', 'string', 'regex:/^[A-Za-z0-9-]{4,20}$/', 'unique:students,student_number', 'unique:users,username'],
            'first_name'        => self::NAME,
            'middle_name'       => self::MIDDLE_NAME,
            'last_name'         => self::NAME,
            'birthdate'         => ['required', 'date', 'before:today', 'after:1930-01-01'],
            'sex'               => ['required', Rule::in(['Male', 'Female'])],
            'email'             => ['required', 'email', 'max:120', 'unique:users,email'],
            'contact_number'    => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address'           => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
        ] + $this->enrollmentRules();
    }

    public function after(): array
    {
        return [fn ($validator) => $this->checkSection($validator)];
    }
}
