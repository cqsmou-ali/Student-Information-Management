<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** /dashboard sends each user to the dashboard of their own role. */
    public function home(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->homeRoute());
    }

    /** Phase 1 shell. Cards and tables are added in later phases. */
    public function show(Request $request): View
    {
        $titles = ['admin' => 'Administrator dashboard', 'staff' => 'Registrar dashboard', 'student' => 'Student dashboard'];

        $stats = null;
        if (in_array($request->user()->role, ['admin', 'staff'], true)) {
            $students = Student::visible();
            $byCourse = Student::visible()->selectRaw('course_id, count(*) as total')->groupBy('course_id')->pluck('total', 'course_id');
            $stats = [
                'total'    => (clone $students)->count(),
                'enrolled' => (clone $students)->where('enrollment_status', 'Enrolled')->count(),
                'courses'  => Course::orderBy('course_code')->get()->map(fn ($c) => ['code' => $c->course_code, 'count' => $byCourse[$c->id] ?? 0]),
            ];
        }

        return view('dashboard', ['title' => $titles[$request->user()->role], 'stats' => $stats]);
    }
}
