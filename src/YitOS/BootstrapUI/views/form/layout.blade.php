<form action="{{ $handle_url }}" method="post" class="form-horizontal" enctype="multipart/form-data" role="form">
  
  {{-- Begin: Dataform --}}
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
    <h4 class="modal-title">
      <div class="caption">
        <i class="{{ $modal_title_icon }} font-dark"></i>
        <span class="caption-subject font-dark">{{ $modal_title }}</span>
      </div>
    </h4>
    @if (count($sections) > 1)
    <div class="tabbable-line">
      <ul class="nav nav-tabs">
        @foreach ($sections as $section_name => $section)
        <li class="{{ $section['is_active'] ? 'active' : '' }}"><a href="javascript:;" data-target="#tab_{{ $section_name }}" data-toggle="tab"> {{ $section['label'] }} </a></li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>
  <div class="modal-body tab-content">
    @foreach ($sections as $section_name => $section)
    <div id="tab_{{ $section_name }}" class="tab-pane-modal">
      @foreach ($section['elements'] as $element)
      @include($element['template'], $element)
      @endforeach
    </div>
    @endforeach
  </div>
  <div class="modal-footer">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="method" value="{{ $method }}">
    @if ($data)
    <input type="hidden" name="__" value="{{ $data['_id'] }}">
    @endif
    <button type="button" data-dismiss="modal" class="btn btn-outline dark">{{ trans('ui::form.modal.button_close') }} </button>
    <button type="submit" class="btn green mt-ladda-btn ladda-button" data-style="expand-left" data-spinner-color="#fff">
      <span class="ladda-label">
        {{ trans('ui::form.modal.button_submit') }} 
      </span>
    </button>
  </div>
  {{-- End: Dataform --}}
</form>
