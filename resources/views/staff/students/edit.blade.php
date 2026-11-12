@extends('layouts.cvsu')
@section('title', 'Edit student')
@section('content')
<h1>Edit profile: {{ $student->fullName() }}</h1>
@if ($errors->any())<div class="alert alert-error" role="alert">Please fix the highlighted fields.</div>@endif
<form method="POST" action="{{ route('staff.students.update', $student) }}" novalidate>
  @csrf
  @method('PUT')
  <div class="card wide">
    <h3>Personal information</h3>
    <p class="muted">Student number: <strong>{{ $student->student_number }}</strong> (cannot be changed)</p>
    @include('staff.students._personal')
  </div>
  <div class="form-actions">
    <button type="submit" class="btn">Save changes</button>
    <a class="btn btn-light" href="{{ route('staff.students.show', $student) }}">Cancel</a>
  </div>
</form>
@endsection
