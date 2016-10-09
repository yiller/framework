<label class="control-label col-md-3">
  @if (isset($required) && $required)
  <span class="required"> * </span>
  @endif
  {{ $label }}
</label>