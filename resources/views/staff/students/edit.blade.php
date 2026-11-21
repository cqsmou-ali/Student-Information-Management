@extends('layouts.cvsu')
@section('title', 'Edit student')
@section('content')
<h1>Edit profile: {{ $student->fullName() }}</h1>
@if ($errors->hasAny(array_diff($errors->keys(), ['loaded_at'])))<div class="alert alert-error" role="alert">Please fix the highlighted fields.</div>@endif
<form method="POST" action="{{ route('staff.students.update', $student) }}" novalidate>
  @csrf
  @method('PUT')
  @include('staff.students._stale', ['reload' => route('staff.students.edit', $student)])
  <div class="card wide">
    <h3>Personal information</h3>
    <p class="muted">Student number: <strong>{{ $student->student_number }}</strong> (cannot be changed)</p>
    @include('staff.students._personal')
  </div>
  <div class="card wide">
    <h3>Reason for change</h3>
    <p class="muted">Required when you change the student's name, birthdate or sex. It is saved in the activity log, together with the old and new values.</p>
    <x-field name="change_reason" label="Reason" maxlength="255" placeholder="e.g. Correction per PSA birth certificate submitted on 2026-11-20" />
  </div>
  <div class="form-actions">
    <button type="submit" class="btn">Save changes</button>
    <a class="btn btn-light" href="{{ route('staff.students.show', $student) }}">Cancel</a>
  </div>
</form>
@endsection
