<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Section;
use App\Models\Student;
use App\Models\YearLevel;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Student lists and information reports (screen, print, CSV). */
class ReportController extends Controller
{
    private const FILTERS = ['q', 'course_id', 'year_level_id', 'section_id', 'status'];

    public function index(Request $request): View
    {
        $filters = $request->only(self::FILTERS);
        $students = $this->query($request)->get();
        ActivityLog::record('report_generated', 'Student list report (' . $students->count() . ' students)');

        $courses = Course::orderBy('course_code')->pluck('course_code', 'id');
        $years = YearLevel::orderBy('id')->pluck('name', 'id');
        $sections = Section::orderBy('course_id')->orderBy('year_level_id')->orderBy('section_name')->get()
            ->mapWithKeys(fn ($s) => [$s->id => ($courses[$s->course_id] ?? '?') . ' · ' . ($years[$s->year_level_id] ?? '?') . ' · ' . $s->section_name]);

        return view('staff.reports.index', [
            'students' => $students, 'filters' => $filters, 'courses' => $courses, 'years' => $years, 'sections' => $sections,
            'statuses' => array_combine(Student::STATUSES, Student::STATUSES),
            'byStatus' => $students->groupBy('enrollment_status')->map->count(),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $students = $this->query($request)->get();
        ActivityLog::record('report_exported', 'Exported student list to CSV (' . $students->count() . ' students)');

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student No.', 'Last name', 'First name', 'Middle name', 'Course', 'Year level', 'Section', 'Status', 'Email', 'Contact', 'Date enrolled']);
            foreach ($students as $s) {
                fputcsv($out, array_map([Csv::class, 'safe'], [
                    $s->student_number, $s->last_name, $s->first_name, $s->middle_name, $s->course?->course_code, $s->yearLevel?->name,
                    $s->section?->section_name, $s->enrollment_status, $s->email, $s->contact_number, $s->date_enrolled?->format('Y-m-d'),
                ]));
            }
            fclose($out);
        }, 'student-list-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Printable information sheet for one student: personal and enrollment details (no grades: out of scope). */
    public function studentSheet(Request $request, Student $student): View
    {
        $student->load(['course', 'yearLevel', 'section']);
        ActivityLog::record('report_generated', 'Student information sheet of ' . $student->student_number, subject: $student);

        return view('staff.reports.student', ['student' => $student, 'preparedBy' => $request->user()]);
    }

    /** Head count by course, then year level, then section (optionally for one enrollment status). */
    public function summary(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(Student::STATUSES)]])['status'] ?? null;

        $counts = Student::visible()->when($status, fn ($q) => $q->where('enrollment_status', $status))
            ->selectRaw('course_id, year_level_id, section_id, count(*) as total')
            ->groupBy('course_id', 'year_level_id', 'section_id')->get();
        $years = YearLevel::orderBy('id')->get();
        $sections = Section::orderBy('section_name')->get();

        $report = Course::orderBy('course_code')->get()->map(function ($course) use ($counts, $years, $sections) {
            $inCourse = $counts->where('course_id', $course->id);

            $rows = $years->map(function ($year) use ($course, $inCourse, $sections) {
                $inYear = $inCourse->where('year_level_id', $year->id);
                $list = $sections->where('course_id', $course->id)->where('year_level_id', $year->id)
                    ->map(fn ($s) => ['name' => $s->section_name, 'total' => (int) $inYear->where('section_id', $s->id)->sum('total')])->values();

                return ['name' => $year->name, 'sections' => $list, 'unassigned' => (int) $inYear->whereNull('section_id')->sum('total'), 'total' => (int) $inYear->sum('total')];
            })->filter(fn ($r) => $r['total'] > 0 || $r['sections']->isNotEmpty())->values();

            return ['code' => $course->course_code, 'name' => $course->course_name, 'total' => (int) $inCourse->sum('total'), 'years' => $rows];
        });

        $grandTotal = (int) $counts->sum('total');
        ActivityLog::record('report_generated', 'Summary by course, year level and section (' . $grandTotal . ' students' . ($status ? ', ' . $status : '') . ')');

        return view('staff.reports.summary', [
            'report' => $report, 'grandTotal' => $grandTotal, 'status' => $status,
            'statuses' => Student::STATUSES,
        ]);
    }

    private function query(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return Student::visible()->with(['course', 'yearLevel', 'section'])
            ->filter($request->only(self::FILTERS))->orderBy('last_name')->orderBy('first_name');
    }
}
