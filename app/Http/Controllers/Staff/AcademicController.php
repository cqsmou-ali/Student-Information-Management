<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\Student;
use App\Models\YearLevel;
use Illuminate\View\View;

/** Courses, year levels and sections: read-only for staff ("limited"). Admin manages them. */
class AcademicController extends Controller
{
    public function index(): View
    {
        return view('staff.academics.index', [
            'courses'      => Course::orderBy('course_code')->get(),
            'years'        => YearLevel::orderBy('id')->get(),
            'sections'     => Section::orderBy('section_name')->get()->groupBy('course_id'),
            'courseCounts' => Student::visible()->selectRaw('course_id, count(*) as total')->groupBy('course_id')->pluck('total', 'course_id'),
            'sectionCounts' => Student::visible()->whereNotNull('section_id')->selectRaw('section_id, count(*) as total')->groupBy('section_id')->pluck('total', 'section_id'),
        ]);
    }
}
