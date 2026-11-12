<div class="form-grid">
  <x-select-field name="course_id" label="Course" :options="$courses" :value="$student->course_id ?? null" required />
  <x-select-field name="year_level_id" label="Year level" :options="$years" :value="$student->year_level_id ?? null" required />
  <x-select-field name="section_id" label="Section" :options="$sections" :value="$student->section_id ?? null" placeholder="No section yet" hint="Must match the course and year level." />
  <x-select-field name="enrollment_status" label="Enrollment status" :options="$statuses" :value="$student->enrollment_status ?? 'Enrolled'" required />
  <x-field name="date_enrolled" label="Date enrolled" type="date" :value="isset($student) ? $student->date_enrolled?->format('Y-m-d') : date('Y-m-d')" required />
</div>
