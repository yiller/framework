<form action="{{ $handle_url }}" method="post" class="form-horizontal form-ajax" enctype="multipart/form-data" role="form">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
    <h4 class="modal-title">
      <div class="caption">
        <i class="{{ $icon or 'fa fa-cubes' }} font-dark"></i>
        <span class="caption-subject font-dark">{{ $title }}</span>
      </div>
    </h4>
    {{ $header_extend or '' }}
  </div>
  <div class="modal-body">
    {{ $content }}
  </div>
  <div class="modal-footer">
    {{ $footer_extend or '' }}
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="method" value="{{ $method }}">
    @if ($data)
    <input type="hidden" name="__" value="{{ $data['__'] }}">
    @endif
    <button type="button" data-dismiss="modal" class="btn btn-outline dark">{{ trans('ui::form.modal.button_close') }} </button>
    @if ($enabled)
    <button type="submit" class="btn green mt-ladda-btn ladda-button" data-style="expand-left" data-spinner-color="#fff">
      <span class="ladda-label">
        {{ $submit or trans('ui::form.modal.button_submit') }}
      </span>
    </button>
    @endif
  </div>
</form>