@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'placeholder' => null, 'maxlength' => null, 'readonly' => false])
<div class="field {{ $errors->has($name) ? 'has-error' : '' }}">
  <label for="{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
  <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif @if ($maxlength) maxlength="{{ $maxlength }}" @endif @required($required) @readonly($readonly)>
  @if ($hint)<small class="muted">{{ $hint }}</small>@endif
  @error($name)<div class="field-error">{{ $message }}</div>@enderror
</div>
