<div class="form-grid">
  <x-field name="first_name" label="First name" :value="$student->first_name ?? null" required maxlength="60" />
  <x-field name="middle_name" label="Middle name" :value="$student->middle_name ?? null" maxlength="60" />
  <x-field name="last_name" label="Last name" :value="$student->last_name ?? null" required maxlength="60" />
  <x-field name="birthdate" label="Birthdate" type="date" :value="isset($student) ? $student->birthdate?->format('Y-m-d') : null" required />
  <x-select-field name="sex" label="Sex" :options="['Male' => 'Male', 'Female' => 'Female']" :value="$student->sex ?? null" required />
  <x-field name="email" label="Email" type="email" :value="$student->email ?? null" required maxlength="120" hint="Password reset links are sent here." />
  <x-field name="contact_number" label="Contact number" type="tel" :value="$student->contact_number ?? null" maxlength="20" />
  <x-field name="emergency_contact" label="Emergency contact" :value="$student->emergency_contact ?? null" maxlength="150" />
</div>
<x-field name="address" label="Address" :value="$student->address ?? null" maxlength="255" />
