@props(['name', 'label', 'options', 'value' => null, 'required' => false, 'placeholder' => 'Select…', 'hint' => null])
<div class="field {{ $errors->has($name) ? 'has-error' : '' }}">
  <label for="{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
  <select id="{{ $name }}" name="{{ $name }}" @required($required)>
    @unless ($required)<option value="">{{ $placeholder }}</option>@endunless
    @if ($required)<option value="" disabled @selected(old($name, $value) === null || old($name, $value) === '')>{{ $placeholder }}</option>@endif
    @foreach ($options as $key => $text)
      <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>
    @endforeach
  </select>
  @if ($hint)<small class="muted">{{ $hint }}</small>@endif
  @error($name)<div class="field-error">{{ $message }}</div>@enderror
</div>
