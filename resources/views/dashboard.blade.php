@extends('layouts.cvsu')
@section('title', $title)
@section('content')
<h1>{{ $title }}</h1>
<section class="welcome">
  <h2>Welcome, {{ auth()->user()->username }}.</h2>
  <p>You are signed in as {{ auth()->user()->role }}.@unless ($stats) Your modules will appear in the menu once the next build phases are added.@endunless</p>
</section>
@if ($stats)
<div class="tiles">
  <div class="card tile"><div class="eyebrow">Registered students</div><div class="big num">{{ $stats['total'] }}</div></div>
  <div class="card tile"><div class="eyebrow">Currently enrolled</div><div class="big num">{{ $stats['enrolled'] }}</div></div>
  @foreach ($stats['courses'] as $c)
    <div class="card tile"><div class="eyebrow">{{ $c['code'] }}</div><div class="big num">{{ $c['count'] }}</div></div>
  @endforeach
</div>
@if (auth()->user()->role === 'staff')
<div class="card">
  <h3>Quick actions</h3>
  <div class="form-actions">
    <a class="btn" href="{{ route('staff.students.create') }}">Register student</a>
    <a class="btn btn-light" href="{{ route('staff.students.index') }}">Find a student</a>
    <a class="btn btn-light" href="{{ route('staff.reports') }}">Generate student list</a>
  </div>
</div>
@endif
@endif
<div class="tiles">
  <div class="card tile">
    <div class="eyebrow">Account</div>
    <div class="big">{{ auth()->user()->username }}</div>
    <div class="muted">{{ auth()->user()->email }} &middot; {{ ucfirst(auth()->user()->role) }}</div>
  </div>
  <div class="card tile">
    <div class="eyebrow">Security</div>
    <p>Keep your account safe by updating your password regularly.</p>
    <a href="{{ route('password.change') }}"><strong>Change password</strong></a>
  </div>
</div>
@endsection
