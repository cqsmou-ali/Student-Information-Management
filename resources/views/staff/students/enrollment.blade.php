@extends('layouts.cvsu')
@section('title', 'Enrollment')
@section('content')
<h1>Enrollment: {{ $student->fullName() }}</h1>
@if ($errors->any())<div class="alert alert-error" role="alert">Please fix the highlighted fields.</div>@endif
<form method="POST" action="{{ route('staff.students.enrollment.update', $student) }}" novalidate>
  @csrf
  @method('PUT')
  <div class="card wide">
    <h3>Course, year level and section</h3>
    <p class="muted">Student number: <strong>{{ $student->student_number }}</strong></p>
    @include('staff.students._enrollment')
  </div>
  <div class="form-actions">
    <button type="submit" class="btn">Save enrollment</button>
    <a class="btn btn-light" href="{{ route('staff.students.show', $student) }}">Cancel</a>
  </div>
</form>
@endsection
