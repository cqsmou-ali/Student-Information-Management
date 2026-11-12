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

    private function query(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return Student::visible()->with(['course', 'yearLevel', 'section'])
            ->filter($request->only(self::FILTERS))->orderBy('last_name')->orderBy('first_name');
    }
}
