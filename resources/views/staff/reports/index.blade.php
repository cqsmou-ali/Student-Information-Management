@extends('layouts.cvsu')
@section('title', 'Reports')
@section('content')
<div class="page-head">
  <h1>Student list report</h1>
  <div class="page-actions">
    <a class="btn btn-light btn-sm" href="{{ route('staff.reports.summary') }}">Summary by section</a>
    <a class="btn btn-light btn-sm" href="{{ route('staff.students.export', $filters) }}">Download CSV</a>
    <button type="button" class="btn btn-sm" data-print>Print</button>
  </div>
</div>
<div class="card">
  @include('staff.students._filters', ['action' => route('staff.reports')])
</div>
<div class="card">
  <p class="summary-line">
    <strong>{{ $students->count() }}</strong> student(s) &middot; generated {{ now()->format('F j, Y g:i A') }}
    @foreach ($byStatus as $label => $n) &middot; {{ $label }}: {{ $n }} @endforeach
  </p>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Student no.</th><th>Last name</th><th>First name</th><th>Middle name</th><th>Course</th><th>Year</th><th>Section</th><th>Status</th></tr></thead>
      <tbody>
      @forelse ($students as $s)
        <tr><td>{{ $s->student_number }}</td><td>{{ $s->last_name }}</td><td>{{ $s->first_name }}</td><td>{{ $s->middle_name }}</td><td>{{ $s->course?->course_code }}</td><td>{{ $s->yearLevel?->name }}</td><td>{{ $s->section?->section_name ?? '—' }}</td><td>{{ $s->enrollment_status }}</td></tr>
      @empty
        <tr><td colspan="8" class="empty">No students match the selected filters.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
