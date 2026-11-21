{{-- Concurrent-edit guard. The version always comes from the database, never from old(), so a refused save cannot be resent stale. --}}
<input type="hidden" name="loaded_at" value="{{ $student->editVersion() }}">
@error('loaded_at')
  <div class="alert alert-warning" role="alert">
    <strong>{{ $message }}</strong><br>
    Your edits are still in the form below. Saving again will replace the other user's changes.
    To see the latest saved version first, <a href="{{ $reload }}">reload this page</a> (your edits will be discarded)
    or <a href="{{ route('staff.students.show', $student) }}" target="_blank" rel="noopener">open the current record in a new tab</a>.
  </div>
@enderror
