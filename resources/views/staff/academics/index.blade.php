@extends('layouts.cvsu')
@section('title', 'Courses & sections')
@section('content')
<h1>Courses &amp; sections</h1>
<div class="alert alert-info">This is a read-only view. Courses, year levels and sections are managed by the administrator. You can assign a student to any of these sections from the student's enrollment page.</div>
@foreach ($courses as $c)
  <div class="card">
    <h3>{{ $c->course_code }} – {{ $c->course_name }}</h3>
    <p class="muted">{{ $courseCounts[$c->id] ?? 0 }} student(s)@if ($c->description) &middot; {{ $c->description }}@endif</p>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Year level</th><th>Section</th><th>Students</th></tr></thead>
        <tbody>
        @forelse (($sections[$c->id] ?? collect())->sortBy('year_level_id') as $s)
          <tr><td>{{ $years->firstWhere('id', $s->year_level_id)?->name }}</td><td>{{ $s->section_name }}</td><td>{{ $sectionCounts[$s->id] ?? 0 }}</td></tr>
        @empty
          <tr><td colspan="3" class="empty">No sections yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endforeach
@endsection
