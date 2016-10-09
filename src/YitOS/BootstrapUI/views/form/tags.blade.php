<div class="form-group">
  @include('ui::form.label')
  <div class="col-md-9">
    @inject('element', 'YitOS\Foundation\BootstrapUI\Form\Tags')
    {!! $element::load()
          ->name($name)
          ->extra($extra)
          ->placeholder($placeholder)
          ->helper(isset($helper)?$helper:'')
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>