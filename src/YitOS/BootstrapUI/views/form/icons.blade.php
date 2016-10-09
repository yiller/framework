<div class="form-group">
  @include('ui::form.label')
  <div class="col-md-4">
    @inject('element', 'YitOS\Foundation\BootstrapUI\Form\Icons')
    {!! $element::load()
          ->name($name)
          ->extra($extra)
          ->helper(isset($helper)?$helper:'')
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>
