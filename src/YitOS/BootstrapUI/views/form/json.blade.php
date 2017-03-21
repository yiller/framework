<div class="form-group">
  @if (isset($label))
  <label class="control-label col-md-3">
    @if (isset($required) && $required)
    <span class="required"> * </span>
    @endif
    {{ $label }}
  </label>
  <div class="col-md-9 mt-repeater-v2">
  @else
  <div class="col-md-12 mt-repeater-v2">
  @endif
    @inject('element', 'YitOS\BootstrapUI\Form\Repeat')
    {!! $element::load($name,$items)
          ->header($header)
          ->line($line)
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>