<div class="form-group">
  @include('ui::form.label')
  <div class="col-md-9">
    @inject('element', 'YitOS\BootstrapUI\Form\Choose')
    {!! $element::load()
          ->name($name)
          ->options($options)
          ->multi()
          ->helper(isset($helper)?$helper:'')
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>