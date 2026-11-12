@extends('layouts.cvsu')
@section('title', 'Reset password')
@section('content')
<div class="card">
  <h2>Choose a new password</h2>
  <p class="lead">Pick a strong password you have not used before.</p>
  <form method="POST" action="{{ route('password.store') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
      <label for="email">Email address</label>
      <div class="input-icon">
        @include('partials.icon', ['name' => 'mail'])
        <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" maxlength="120" required autocomplete="email">
      </div>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
      <label for="password">New password</label>
      <div class="input-icon has-toggle">
        @include('partials.icon', ['name' => 'lock'])
        <input type="password" id="password" name="password" required autocomplete="new-password" autofocus>
        <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">Show</button>
      </div>
      <small class="muted">At least {{ config('sis.min_password_length') }} characters with uppercase, lowercase, and a number.</small>
      @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
      <label for="password_confirmation">Confirm new password</label>
      <div class="input-icon">
        @include('partials.icon', ['name' => 'lock'])
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
      </div>
    </div>
    <button type="submit" class="btn btn-block">Reset password</button>
  </form>
  <a class="back-link" href="{{ route('login') }}">&larr; Back to log in</a>
</div>
@endsection
