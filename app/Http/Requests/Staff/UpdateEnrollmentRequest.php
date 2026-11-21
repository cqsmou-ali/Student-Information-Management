<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/** Registrar: change a student's course, year level, section and enrollment status. */
class UpdateEnrollmentRequest extends FormRequest
{
    use ChecksEnrollment, PreventsStaleEdits;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->enrollmentRules();
    }

    public function after(): array
    {
        return [
            fn ($validator) => $this->checkNotStale($validator),
            fn ($validator) => $this->checkSection($validator),
        ];
    }
}
