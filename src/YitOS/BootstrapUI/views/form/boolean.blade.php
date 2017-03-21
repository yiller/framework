<div class="form-group">
  @include('ui::form.label')
  <div class="col-md-4">
    @inject('element', 'YitOS\BootstrapUI\Form\Boolean')
    {!! $element::load($name,$options,$extra)
          ->helper(isset($helper)?$helper:'')
          ->render($data, isset($default)?$default:'') !!}
  </div>
</div>