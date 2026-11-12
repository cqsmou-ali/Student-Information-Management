@extends('layouts.cvsu')
@section('title', $student->fullName())
@section('content')
<div class="page-head">
  <h1>{{ $student->fullName() }}</h1>
  <div class="page-actions">
    <a class="btn btn-light btn-sm" href="{{ route('staff.students.edit', $student) }}">Edit profile</a>
    <a class="btn btn-sm" href="{{ route('staff.students.enrollment.edit', $student) }}">Edit enrollment</a>
  </div>
</div>
@if (session('temp_password'))
  <div class="alert alert-warning" role="alert">
    Give this temporary password to the student: <code class="temp">{{ session('temp_password') }}</code><br>
    Username: <strong>{{ $student->student_number }}</strong>. It is shown only once, and the student must change it at first login.
  </div>
@endif
<div class="card wide">
  <h3>Enrollment</h3>
  <dl class="details">
    <div><dt>Student number</dt><dd>{{ $student->student_number }}</dd></div>
    <div><dt>Status</dt><dd><span class="badge {{ $student->enrollment_status === 'Enrolled' ? '' : 'warn' }}">{{ $student->enrollment_status }}</span></dd></div>
    <div><dt>Course</dt><dd>{{ $student->course?->course_code }} – {{ $student->course?->course_name }}</dd></div>
    <div><dt>Year level</dt><dd>{{ $student->yearLevel?->name }}</dd></div>
    <div><dt>Section</dt><dd>{{ $student->section?->section_name ?? 'Not assigned' }}</dd></div>
    <div><dt>Date enrolled</dt><dd>{{ $student->date_enrolled?->format('F j, Y') }}</dd></div>
  </dl>
</div>
<div class="card wide">
  <h3>Personal information</h3>
  <dl class="details">
    <div><dt>Birthdate</dt><dd>{{ $student->birthdate?->format('F j, Y') }}</dd></div>
    <div><dt>Sex</dt><dd>{{ $student->sex }}</dd></div>
    <div><dt>Email</dt><dd>{{ $student->email }}</dd></div>
    <div><dt>Contact number</dt><dd>{{ $student->contact_number ?: '—' }}</dd></div>
    <div><dt>Address</dt><dd>{{ $student->address ?: '—' }}</dd></div>
    <div><dt>Emergency contact</dt><dd>{{ $student->emergency_contact ?: '—' }}</dd></div>
  </dl>
</div>
<div class="card wide">
  <h3>Academic information</h3>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Subject</th><th>Units</th><th>School year</th><th>Sem</th><th>Grade</th><th>Remarks</th></tr></thead>
      <tbody>
      @forelse ($student->grades as $g)
        <tr><td>{{ $g->subject?->subject_code }} – {{ $g->subject?->subject_title }}</td><td>{{ $g->subject?->units }}</td><td>{{ $g->school_year }}</td><td>{{ $g->semester }}</td><td>{{ $g->grade !== null ? number_format($g->grade, 2) : '—' }}</td><td>{{ $g->remarks }}</td></tr>
      @empty
        <tr><td colspan="6" class="empty">No academic records yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<a href="{{ route('staff.students.index') }}">&larr; Back to students</a>
@endsection
