@extends('layouts.cvsu')
@section('title', 'Information sheet – ' . $student->student_number)
@section('content')
<div class="page-head no-print">
  <h1>Student information sheet</h1>
  <div class="page-actions">
    <a class="btn btn-light btn-sm" href="{{ route('staff.students.show', $student) }}">Back to record</a>
    <button type="button" class="btn btn-sm" data-print>Print</button>
  </div>
</div>
<div class="card wide sheet">
  <div class="sheet-head">
    <small>Republic of the Philippines</small>
    <strong>Cavite State University – Don Severino de las Alas Campus</strong>
    <span>College of Engineering and Information Technology</span>
    <h2>Student Information Sheet</h2>
  </div>

  <h3>Student</h3>
  <dl class="details">
    <div><dt>Student number</dt><dd>{{ $student->student_number }}</dd></div>
    <div><dt>Name</dt><dd>{{ $student->fullName() }}</dd></div>
    <div><dt>Status</dt><dd>{{ $student->enrollment_status }}</dd></div>
  </dl>

  <h3>Enrollment</h3>
  <dl class="details">
    <div><dt>Course</dt><dd>{{ $student->course?->course_code }} – {{ $student->course?->course_name }}</dd></div>
    <div><dt>Year level</dt><dd>{{ $student->yearLevel?->name }}</dd></div>
    <div><dt>Section</dt><dd>{{ $student->section?->section_name ?? 'Not assigned' }}</dd></div>
    <div><dt>Date enrolled</dt><dd>{{ $student->date_enrolled?->format('F j, Y') }}</dd></div>
  </dl>

  <h3>Personal information</h3>
  <dl class="details">
    <div><dt>Birthdate</dt><dd>{{ $student->birthdate?->format('F j, Y') }}</dd></div>
    <div><dt>Sex</dt><dd>{{ $student->sex }}</dd></div>
    <div><dt>Email</dt><dd>{{ $student->email }}</dd></div>
    <div><dt>Contact number</dt><dd>{{ $student->contact_number ?: '—' }}</dd></div>
    <div><dt>Address</dt><dd>{{ $student->address ?: '—' }}</dd></div>
    <div><dt>Emergency contact</dt><dd>{{ $student->emergency_contact ?: '—' }}</dd></div>
  </dl>

  <div class="sheet-foot">
    <div class="signature">
      <span class="sig-line"></span>
      {{ $preparedBy->full_name ?: $preparedBy->username }}@if ($preparedBy->position), {{ $preparedBy->position }}@endif<br>
      <small>Prepared by</small>
    </div>
    <p class="muted">Generated {{ now()->format('F j, Y g:i A') }}. Confidential: contains personal information of the student.</p>
  </div>
</div>
@endsection
