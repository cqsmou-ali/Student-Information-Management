<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStudentRequest;
use App\Http\Requests\Staff\UpdateEnrollmentRequest;
use App\Http\Requests\Staff\UpdateStudentRequest;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Registrar: register students, update profiles, manage enrollment. No delete (admin only). Validation lives in App\Http\Requests\Staff. */
class StudentController extends Controller
{
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

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Temporary password: mixed case + digits, so it passes our own password rules. The student must change it at first login.
        $temporary = 'Cv' . Str::lower(Str::random(5)) . random_int(1000, 9999);

        $student = DB::transaction(function () use ($data, $temporary) {
            $user = User::create([
                'username' => $data['student_number'], 'email' => $data['email'], 'password_hash' => $temporary,
                'role' => 'student', 'must_change_password' => true,
            ]);

            return $user->student()->create($data); // user_id is set by the relation, it is not mass-assignable
        });

        ActivityLog::record('student_registered', 'Registered student ' . $student->student_number, subject: $student);

        return redirect()->route('staff.students.show', $student)
            ->with('status', 'Student registered. A student account was created.')
            // Encrypted, so the password is never stored in plain text in the sessions table (even with SESSION_ENCRYPT=false).
            ->with('temp_password', Crypt::encryptString($temporary));
    }

    public function show(Student $student): Response
    {
        $student->load(['course', 'yearLevel', 'section', 'grades.subject']);
        ActivityLog::record('student_viewed', 'Viewed record of ' . $student->student_number, subject: $student);

        $temporary = null;
        if (is_string($sealed = session('temp_password'))) {
            try {
                $temporary = Crypt::decryptString($sealed);
            } catch (DecryptException) {
                // tampered or from an old APP_KEY: just don't show it
            }
        }

        $response = response()->view('staff.students.show', ['student' => $student, 'temporaryPassword' => $temporary]);

        // The page with the one-time password must not be kept in the browser cache (Back button, shared PCs).
        return $temporary === null ? $response : $response->header('Cache-Control', 'no-store, private');
    }

    public function edit(Student $student): View
    {
        return view('staff.students.edit', ['student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $reason = $data['change_reason'] ?? null; // goes to the audit log, not the student record
        unset($data['change_reason']);

        DB::transaction(function () use ($student, $data, $reason) {
            $student->fill($data);
            $changes = ActivityLog::diff($student); // old -> new, read before save() clears it
            $student->save();
            if ($student->user_id) {
                $student->user()->update(['email' => $data['email']]);
            }
            $details = 'Updated profile of ' . $student->student_number . ($changes ? ' (' . implode(', ', array_keys($changes)) . ')' : '');
            if ($reason !== null) {
                $details .= '. Reason: ' . $reason;
                $changes['change_reason'] = $reason;
            }
            ActivityLog::record('student_updated', $details, subject: $student, changes: $changes);
        });

        return redirect()->route('staff.students.show', $student)->with('status', 'Student profile updated.');
    }

    public function editEnrollment(Student $student): View
    {
        return view('staff.students.enrollment', ['student' => $student] + $this->lookups());
    }

    public function updateEnrollment(UpdateEnrollmentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        $before = $student->course_id . '/' . $student->year_level_id . '/' . ($student->section_id ?? '-') . '/' . $student->enrollment_status;
        $student->fill($data);
        $changes = ActivityLog::diff($student);
        $student->save();
        $after = $student->course_id . '/' . $student->year_level_id . '/' . ($student->section_id ?? '-') . '/' . $student->enrollment_status;

        ActivityLog::record('enrollment_updated', 'Enrollment of ' . $student->student_number . ' changed (course/year/section/status ' . $before . ' to ' . $after . ')',
            subject: $student, changes: $changes);

        return redirect()->route('staff.students.show', $student)->with('status', 'Enrollment information updated.');
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
