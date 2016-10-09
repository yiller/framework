<form action="@yield('modal_url')" method="post" class="form-horizontal" enctype="multipart/form-data" role="form">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
    <h4 class="modal-title">
      <div class="caption">
        <i class="@yield('modal_icon') font-dark"></i>
        <span class="caption-subject font-dark">@yield('modal_title')</span>
      </div>
    </h4>
    @yield('modal_header_extend')
  </div>
  <div class="modal-body tab-content">
    @section('modal_content')
    @show
  </div>
  <div class="modal-footer">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    @yield('modal_footer_extend')
    <button type="button" data-dismiss="modal" class="btn btn-outline dark">@yield('modal_cancel', trans('ui::form.modal.button_close')) </button>
    <button type="submit" class="btn green mt-ladda-btn ladda-button" data-style="expand-left" data-spinner-color="#fff">
      <span class="ladda-label">
        @yield('modal_submit', trans('ui::form.modal.button_submit')) 
      </span>
    </button>
  </div>
</form>