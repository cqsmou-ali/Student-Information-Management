<form method="GET" action="{{ $action }}" class="filter-bar no-print" role="search">
  <div class="field grow">
    <label for="q">Search</label>
    <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Name, student number or email">
  </div>
  <div class="field"><label for="course_id">Course</label>
    <select id="course_id" name="course_id"><option value="">All</option>@foreach ($courses as $id => $t)<option value="{{ $id }}" @selected((string) ($filters['course_id'] ?? '') === (string) $id)>{{ $t }}</option>@endforeach</select></div>
  <div class="field"><label for="year_level_id">Year level</label>
    <select id="year_level_id" name="year_level_id"><option value="">All</option>@foreach ($years as $id => $t)<option value="{{ $id }}" @selected((string) ($filters['year_level_id'] ?? '') === (string) $id)>{{ $t }}</option>@endforeach</select></div>
  <div class="field"><label for="section_id">Section</label>
    <select id="section_id" name="section_id"><option value="">All</option>@foreach ($sections as $id => $t)<option value="{{ $id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $id)>{{ $t }}</option>@endforeach</select></div>
  <div class="field"><label for="status">Status</label>
    <select id="status" name="status"><option value="">All</option>@foreach ($statuses as $t)<option value="{{ $t }}" @selected(($filters['status'] ?? '') === $t)>{{ $t }}</option>@endforeach</select></div>
  <button type="submit" class="btn btn-sm">Apply</button>
  <a class="btn btn-light btn-sm" href="{{ $action }}">Clear</a>
</form>
