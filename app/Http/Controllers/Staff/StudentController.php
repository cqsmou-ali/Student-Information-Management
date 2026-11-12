<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Registrar: register students, update profiles, manage enrollment. No delete (admin only). */
class StudentController extends Controller
{
    private const NAME = ['required', 'string', 'max:60', "regex:/^[\\pL\\s.'-]+$/u"];

    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $filters = $request->only(['q', 'course_id', 'year_level_id', 'section_id', 'status']);

        $students = Student::visible()->with(['course', 'yearLevel', 'section'])
            ->filter($filters)->orderBy('last_name')->orderBy('first_name')
            ->paginate(15)->withQueryString();

        return view('staff.students.index', ['students' => $students, 'filters' => $filters] + $this->lookups());
    }

    public function create(): View
    {
        return view('staff.students.create', $this->lookups());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_number'    => ['required', 'string', 'regex:/^[A-Za-z0-9-]{4,20}$/', 'unique:students,student_number', 'unique:users,username'],
            'first_name'        => self::NAME,
            'middle_name'       => ['nullable', 'string', 'max:60', "regex:/^[\\pL\\s.'-]+$/u"],
            'last_name'         => self::NAME,
            'birthdate'         => ['required', 'date', 'before:today', 'after:1930-01-01'],
            'sex'               => ['required', Rule::in(['Male', 'Female'])],
            'email'             => ['required', 'email', 'max:120', 'unique:users,email'],
            'contact_number'    => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address'           => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
        ] + $this->enrollmentRules());
        $this->checkSection($data);

        // Temporary password: mixed case + digits, so it passes our own password rules. The student must change it at first login.
        $temporary = 'Cv' . Str::lower(Str::random(5)) . random_int(1000, 9999);

        $student = DB::transaction(function () use ($data, $temporary) {
            $user = User::create([
                'username' => $data['student_number'], 'email' => $data['email'], 'password_hash' => $temporary,
                'role' => 'student', 'must_change_password' => true,
            ]);

            return Student::create($data + ['user_id' => $user->id]);
        });

        ActivityLog::record('student_registered', 'Registered student ' . $student->student_number);

        return redirect()->route('staff.students.show', $student)
            ->with('status', 'Student registered. A student account was created.')
            ->with('temp_password', $temporary);
    }

    public function show(Student $student): View
    {
        $student->load(['course', 'yearLevel', 'section', 'grades.subject']);

        return view('staff.students.show', ['student' => $student]);
    }

    public function edit(Student $student): View
    {
        return view('staff.students.edit', ['student' => $student]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'first_name'        => self::NAME,
            'middle_name'       => ['nullable', 'string', 'max:60', "regex:/^[\\pL\\s.'-]+$/u"],
            'last_name'         => self::NAME,
            'birthdate'         => ['required', 'date', 'before:today', 'after:1930-01-01'],
            'sex'               => ['required', Rule::in(['Male', 'Female'])],
            'email'             => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($student->user_id)],
            'contact_number'    => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'address'           => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($student, $data) {
            $student->fill($data);
            $changed = array_keys($student->getDirty());
            $student->save();
            if ($student->user_id) {
                $student->user()->update(['email' => $data['email']]);
            }
            ActivityLog::record('student_updated', 'Updated profile of ' . $student->student_number . ($changed ? ' (' . implode(', ', $changed) . ')' : ''));
        });

        return redirect()->route('staff.students.show', $student)->with('status', 'Student profile updated.');
    }

    public function editEnrollment(Student $student): View
    {
        return view('staff.students.enrollment', ['student' => $student] + $this->lookups());
    }

    public function updateEnrollment(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate($this->enrollmentRules());
        $this->checkSection($data);

        $before = $student->course_id . '/' . $student->year_level_id . '/' . ($student->section_id ?? '-') . '/' . $student->enrollment_status;
        $student->update($data);
        $after = $student->course_id . '/' . $student->year_level_id . '/' . ($student->section_id ?? '-') . '/' . $student->enrollment_status;

        ActivityLog::record('enrollment_updated', 'Enrollment of ' . $student->student_number . ' changed (course/year/section/status ' . $before . ' to ' . $after . ')');

        return redirect()->route('staff.students.show', $student)->with('status', 'Enrollment information updated.');
    }

    private function enrollmentRules(): array
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
    private function checkSection(array $data): void
    {
        if (! empty($data['section_id'])) {
            $ok = Section::where('id', $data['section_id'])
                ->where('course_id', $data['course_id'])->where('year_level_id', $data['year_level_id'])->exists();
            if (! $ok) {
                throw ValidationException::withMessages(['section_id' => 'That section does not belong to the chosen course and year level.']);
            }
        }
    }

    private function lookups(): array
    {
        $courses = Course::orderBy('course_code')->pluck('course_code', 'id');
        $years = YearLevel::orderBy('id')->pluck('name', 'id');
        $sections = Section::orderBy('course_id')->orderBy('year_level_id')->orderBy('section_name')->get()
            ->mapWithKeys(fn ($s) => [$s->id => ($courses[$s->course_id] ?? '?') . ' · ' . ($years[$s->year_level_id] ?? '?') . ' · ' . $s->section_name]);

        return ['courses' => $courses, 'years' => $years, 'sections' => $sections, 'statuses' => array_combine(Student::STATUSES, Student::STATUSES)];
    }
}
