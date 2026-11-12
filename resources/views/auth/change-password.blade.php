@extends('layouts.cvsu')
@section('title', 'Change password')
@section('content')
<h1>{{ $forced ? 'Set a new password' : 'Change password' }}</h1>
<div class="card narrow">
  @if ($forced)
    <div class="alert alert-warning" role="alert">Your account uses a temporary password. You must choose a new one before continuing.</div>
  @endif
  <form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    @method('PUT')
    <div class="field {{ $errors->has('current_password') ? 'has-error' : '' }}">
      <label for="current_password">{{ $forced ? 'Temporary password' : 'Current password' }}</label>
      <div class="input-icon has-toggle">
        @include('partials.icon', ['name' => 'lock'])
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" data-target="current_password" aria-label="Show password">Show</button>
      </div>
      @error('current_password')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field {{ $errors->has('password') ? 'has-error' : '' }}">
      <label for="password">New password</label>
      <div class="input-icon has-toggle">
        @include('partials.icon', ['name' => 'lock'])
        <input type="password" id="password" name="password" required autocomplete="new-password">
        <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">Show</button>
      </div>
      <small class="muted">At least {{ config('sis.min_password_length') }} characters with uppercase, lowercase, and a number.</small>
      @error('password')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div class="field">
      <label for="password_confirmation">Confirm new password</label>
      <div class="input-icon has-toggle">
        @include('partials.icon', ['name' => 'lock'])
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        <button type="button" class="pw-toggle" data-target="password_confirmation" aria-label="Show password">Show</button>
      </div>
    </div>
    <button type="submit" class="btn">Save password</button>
  </form>
</div>
@endsection
