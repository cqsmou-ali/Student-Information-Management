@extends('layouts.cvsu')
@section('title', 'Register student')
@section('content')
<h1>Register student</h1>
@if ($errors->any())<div class="alert alert-error" role="alert">Please fix the highlighted fields.</div>@endif
<form method="POST" action="{{ route('staff.students.store') }}" novalidate>
  @csrf
  <div class="card wide">
    <h3>Account</h3>
    <div class="form-grid">
      <x-field name="student_number" label="Student number" required maxlength="20" placeholder="e.g. 2026-0001" hint="This becomes the student's username." />
    </div>
    <p class="muted">A temporary password is generated when you save. The student must change it at first login.</p>
  </div>
  <div class="card wide"><h3>Personal information</h3>@include('staff.students._personal')</div>
  <div class="card wide"><h3>Enrollment</h3>@include('staff.students._enrollment')</div>
  <div class="form-actions">
    <button type="submit" class="btn">Register student</button>
    <a class="btn btn-light" href="{{ route('staff.students.index') }}">Cancel</a>
  </div>
</form>
@endsection
