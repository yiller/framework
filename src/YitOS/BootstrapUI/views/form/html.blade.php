<div class="form-group">
  @include('ui::form.label')
  <div class="col-md-9">
    @inject('element', 'YitOS\BootstrapUI\Form\Editor')
    {!! $element::load()
          ->name($name)
          ->extra($extra)
          ->helper(isset($helper)?$helper:'')
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>