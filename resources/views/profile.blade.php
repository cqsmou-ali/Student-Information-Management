@extends('layouts.cvsu')
@section('title', 'My profile')
@section('content')
<h1>My profile</h1>
<div class="card wide">
  <h3>Account</h3>
  <dl class="details">
    <div><dt>Username</dt><dd>{{ $user->username }}</dd></div>
    <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
    <div><dt>Role</dt><dd><span class="badge">{{ ucfirst($user->role) }}</span></dd></div>
    <div><dt>Last login</dt><dd>{{ $user->last_login?->format('F j, Y g:i A') ?? '—' }}</dd></div>
  </dl>
  <p><a href="{{ route('password.change') }}">Change password</a></p>
</div>
@if ($student)
<div class="card wide">
  <h3>Student record</h3>
  <dl class="details">
    <div><dt>Name</dt><dd>{{ $student->fullName() }}</dd></div>
    <div><dt>Course</dt><dd>{{ $student->course?->course_code }}</dd></div>
    <div><dt>Year level</dt><dd>{{ $student->yearLevel?->name }}</dd></div>
    <div><dt>Section</dt><dd>{{ $student->section?->section_name ?? 'Not assigned' }}</dd></div>
    <div><dt>Status</dt><dd>{{ $student->enrollment_status }}</dd></div>
  </dl>
</div>
@endif
@endsection
