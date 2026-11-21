<?php

namespace App\Http\Requests\Staff;

use App\Models\Section;
use App\Models\Student;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Shared by the register and enrollment forms: course / year level / section / status rules. */
trait ChecksEnrollment
{
    protected function enrollmentRules(): array
    {
        return [
            'course_id'         => ['required', 'integer', 'exists:courses,id'],
            'year_level_id'     => ['required', 'integer', 'exists:year_levels,id'],
            'section_id'        => ['nullable', 'integer', 'exists:sections,id'],
            'enrollment_status' => ['required', Rule::in(Student::STATUSES)],
            'date_enrolled'     => ['required', 'date', 'before_or_equal:today', 'after:1990-01-01'],
        ];
    }

    /** A section belongs to one course and year level, so the three choices must agree. */
    protected function checkSection(Validator $validator): void
    {
        $errors = $validator->errors();
        if ($errors->hasAny(['course_id', 'year_level_id', 'section_id']) || ! $this->filled('section_id')) {
            return; // only check once the three values are individually valid
        }

        $ok = Section::where('id', $this->integer('section_id'))
            ->where('course_id', $this->integer('course_id'))
            ->where('year_level_id', $this->integer('year_level_id'))->exists();
        if (! $ok) {
            $errors->add('section_id', 'That section does not belong to the chosen course and year level.');
        }
    }
}
