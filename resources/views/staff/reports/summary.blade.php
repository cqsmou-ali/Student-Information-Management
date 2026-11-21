@extends('layouts.cvsu')
@section('title', 'Summary report')
@section('content')
<div class="page-head">
  <h1>Summary by course, year level and section</h1>
  <div class="page-actions">
    <a class="btn btn-light btn-sm" href="{{ route('staff.reports') }}">Student list</a>
    <button type="button" class="btn btn-sm" data-print>Print</button>
  </div>
</div>
<div class="card">
  <form method="GET" action="{{ route('staff.reports.summary') }}" class="filter-bar no-print">
    <div class="field"><label for="status">Enrollment status</label>
      <select id="status" name="status"><option value="">All</option>@foreach ($statuses as $t)<option value="{{ $t }}" @selected($status === $t)>{{ $t }}</option>@endforeach</select></div>
    <button type="submit" class="btn btn-sm">Apply</button>
    <a class="btn btn-light btn-sm" href="{{ route('staff.reports.summary') }}">Clear</a>
  </form>
  <p class="summary-line">
    <strong>{{ $grandTotal }}</strong> student(s) &middot; {{ $status ? 'status: ' . $status : 'all statuses' }} &middot; generated {{ now()->format('F j, Y g:i A') }}
  </p>
</div>
@foreach ($report as $course)
  <div class="card">
    <h3>{{ $course['code'] }} – {{ $course['name'] }} <span class="badge">{{ $course['total'] }}</span></h3>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Year level</th><th>Section</th><th>Students</th></tr></thead>
        <tbody>
        @forelse ($course['years'] as $year)
          @foreach ($year['sections'] as $s)
            <tr><td>{{ $year['name'] }}</td><td>{{ $s['name'] }}</td><td>{{ $s['total'] }}</td></tr>
          @endforeach
          @if ($year['unassigned'] > 0)
            <tr><td>{{ $year['name'] }}</td><td class="muted">No section yet</td><td>{{ $year['unassigned'] }}</td></tr>
          @endif
          <tr class="subtotal"><td colspan="2">{{ $year['name'] }} total</td><td>{{ $year['total'] }}</td></tr>
        @empty
          <tr><td colspan="3" class="empty">No sections or students.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endforeach
@endsection
