<div class="form-group">
  <label class="control-label col-md-3">
    @if (isset($required) && $required)
    <span class="required"> * </span>
    @endif
    {{ $label }}
  </label>
  <div class="col-md-9 mt-repeater-v2">
    @inject('element', 'YitOS\Foundation\BootstrapUI\Form\Repeat')
    {!! $element::load()
          ->name($name)
          ->header($header_render)
          ->item($line_render)
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>