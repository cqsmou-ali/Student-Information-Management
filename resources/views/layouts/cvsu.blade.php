<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Student Information System') | CvSU CEIT SIS</title>
<link rel="stylesheet" href="{{ asset('css/cvsu.css') }}">
</head>
<body class="{{ auth()->check() ? 'with-sidebar' : 'public' }}">
@auth
<div class="app">
  <nav class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="side-brand">
      <div class="seal" aria-hidden="true">CvSU</div>
      <div><strong>CEIT SIS</strong><span>Cavite State University</span></div>
    </div>
    <ul>
      <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('*.dashboard') ? 'active' : '' }}">@include('partials.icon', ['name' => 'dash'])Dashboard</a></li>
      @if (auth()->user()->role === 'staff')
        <li><a href="{{ route('staff.students.index') }}" class="{{ request()->routeIs('staff.students.index', 'staff.students.show', 'staff.students.edit', 'staff.students.enrollment.*') ? 'active' : '' }}">@include('partials.icon', ['name' => 'users'])Students</a></li>
        <li><a href="{{ route('staff.students.create') }}" class="{{ request()->routeIs('staff.students.create') ? 'active' : '' }}">@include('partials.icon', ['name' => 'plus'])Register student</a></li>
        <li><a href="{{ route('staff.reports') }}" class="{{ request()->routeIs('staff.reports', 'staff.reports.*', 'staff.students.print') ? 'active' : '' }}">@include('partials.icon', ['name' => 'file'])Reports</a></li>
        <li><a href="{{ route('staff.academics') }}" class="{{ request()->routeIs('staff.academics') ? 'active' : '' }}">@include('partials.icon', ['name' => 'book'])Courses &amp; sections</a></li>
      @endif
      <li><a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">@include('partials.icon', ['name' => 'user'])My profile</a></li>
      <li><a href="{{ route('password.change') }}" class="{{ request()->routeIs('password.change') ? 'active' : '' }}">@include('partials.icon', ['name' => 'lock'])Change password</a></li>
    </ul>
    <div class="side-foot">Don Severino de las Alas Campus<br>Indang, Cavite</div>
  </nav>
  <div class="app-main">
    <header class="topbar">
      <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">Menu</button>
      <div class="topbar-title"><small>Republic of the Philippines</small><strong>College of Engineering and Information Technology</strong></div>
      <div class="user-box">
        <span class="user-name">@include('partials.icon', ['name' => 'user']){{ auth()->user()->username }}</span>
        <span class="role-badge">{{ ucfirst(auth()->user()->role) }}</span>
        <form method="POST" action="{{ route('logout') }}" class="inline-form">
          @csrf
          <button type="submit" class="btn btn-light btn-sm">Log out</button>
        </form>
      </div>
    </header>
    <main class="content">
      @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
      @endif
      @yield('content')
    </main>
    <footer class="site-footer">&copy; {{ date('Y') }} Cavite State University &ndash; CEIT. Student Information System.</footer>
  </div>
</div>
@else
<div class="auth-shell">
  <section class="auth-brand">
    <div class="brand-top">
      <div class="seal" aria-hidden="true">CvSU</div>
      <div><small>Republic of the Philippines</small><strong>Cavite State University</strong></div>
    </div>
    <div>
      <div class="brand-college">College of Engineering and Information Technology</div>
      <h1>Student Information System</h1>
      <p>Access your records, schedules and grades in one secure place.</p>
    </div>
    <div class="brand-campus">Don Severino de las Alas Campus &middot; Indang, Cavite</div>
  </section>
  <section class="auth-panel">
    <div class="auth-box">
      @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
      @endif
      @yield('content')
    </div>
    <p class="auth-foot">&copy; {{ date('Y') }} Cavite State University &ndash; CEIT. Student Information System.</p>
  </section>
</div>
@endauth
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
