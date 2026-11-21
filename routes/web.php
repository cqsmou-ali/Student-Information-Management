<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\AcademicController;
use App\Http\Controllers\Staff\ReportController;
use App\Http\Controllers\Staff\StudentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'account.active', 'password.changed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'home'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
    });

    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [DashboardController::class, 'show'])->name('dashboard');

        // Registrar module. Staff can register, view and update students, but cannot delete them (admin only).
        Route::get('students/export', [ReportController::class, 'csv'])->name('students.export');
        Route::resource('students', StudentController::class)->except('destroy');
        Route::get('students/{student}/enrollment', [StudentController::class, 'editEnrollment'])->name('students.enrollment.edit');
        Route::put('students/{student}/enrollment', [StudentController::class, 'updateEnrollment'])->name('students.enrollment.update');
        Route::get('students/{student}/print', [ReportController::class, 'studentSheet'])->name('students.print');
        Route::get('reports', [ReportController::class, 'index'])->name('reports');
        Route::get('reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
        Route::get('academics', [AcademicController::class, 'index'])->name('academics');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
    });
});

require __DIR__.'/auth.php';
