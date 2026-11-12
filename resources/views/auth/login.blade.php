@extends('layouts.cvsu')
@section('title', 'Log in')
@section('content')
<div class="card login-card">
  <h2>Welcome back</h2>
  <p class="lead">Log in with your student number or staff username.</p>
  @if ($errors->any())
    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
  @endif
  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="field">
      <label for="username">Username</label>
      <div class="input-icon">
        @include('partials.icon', ['name' => 'user'])
        <input type="text" id="username" name="username" value="{{ old('username') }}" maxlength="50" placeholder="e.g. 2023-0001" required autofocus autocomplete="username">
      </div>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <div class="input-icon has-toggle">
        @include('partials.icon', ['name' => 'lock'])
        <input type="password" id="password" name="password" maxlength="200" placeholder="Enter your password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">Show</button>
      </div>
    </div>
    <div class="row-between">
      <label class="check"><input type="checkbox" name="remember" value="1"> Remember me</label>
      <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-block">Log in</button>
  </form>
</div>
@endsection
