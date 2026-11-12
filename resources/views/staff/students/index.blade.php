@extends('layouts.cvsu')
@section('title', 'Students')
@section('content')
<div class="page-head">
  <h1>Students</h1>
  <div class="page-actions">
    <a class="btn btn-light btn-sm" href="{{ route('staff.students.export', $filters) }}">Download CSV</a>
    <a class="btn btn-sm" href="{{ route('staff.students.create') }}">Register student</a>
  </div>
</div>
<div class="card">
  @include('staff.students._filters', ['action' => route('staff.students.index')])
</div>
<div class="card">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Student no.</th><th>Name</th><th>Course</th><th>Year</th><th>Section</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @forelse ($students as $s)
        <tr>
          <td>{{ $s->student_number }}</td>
          <td><a href="{{ route('staff.students.show', $s) }}">{{ $s->fullName() }}</a><div class="muted">{{ $s->email }}</div></td>
          <td>{{ $s->course?->course_code }}</td>
          <td>{{ $s->yearLevel?->name }}</td>
          <td>{{ $s->section?->section_name ?? '—' }}</td>
          <td><span class="badge {{ $s->enrollment_status === 'Enrolled' ? '' : (in_array($s->enrollment_status, ['Not Enrolled', 'LOA']) ? 'warn' : 'off') }}">{{ $s->enrollment_status }}</span></td>
          <td><a class="btn btn-light btn-sm" href="{{ route('staff.students.show', $s) }}">View</a></td>
        </tr>
      @empty
        <tr><td colspan="7" class="empty">No students match your search.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  {{ $students->links('pagination.cvsu') }}
</div>
@endsection
