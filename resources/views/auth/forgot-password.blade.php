@extends('layouts.cvsu')
@section('title', 'Forgot password')
@section('content')
<div class="card">
  <h2>Forgot your password?</h2>
  <p class="lead">Enter the email address registered to your account and we will send you a link to choose a new password.</p>
  <form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="field {{ $errors->has('email') ? 'has-error' : '' }}">
      <label for="email">Email address</label>
      <div class="input-icon">
        @include('partials.icon', ['name' => 'mail'])
        <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="120" placeholder="you@example.com" required autofocus autocomplete="email">
      </div>
      @error('email')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-block">Email reset link</button>
  </form>
  <a class="back-link" href="{{ route('login') }}">&larr; Back to log in</a>
</div>
@endsection
