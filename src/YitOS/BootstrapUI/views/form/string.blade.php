<div class="form-group">
  @include('ui::form.label')
  <div class="col-md-4">
    @inject('element', 'YitOS\BootstrapUI\Form\Text')
    {!! $element::load()
          ->name($name)
          ->extra($extra)
          ->placeholder($placeholder)
          ->helper(isset($helper)?$helper:'')
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>