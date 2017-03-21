<div class="row">
  <div class="col-md-12">
    {{-- Begin: DataForm --}}
    <form action="{{ $handle_url }}" method="post" class="form-horizontal form-ajax" enctype="multipart/form-data" role="form">
      <div class="portlet light portlet-fit portlet-datatable bordered">
        <div class="portlet-title">
          <div class="caption">
            <i class="{{ $icon or 'fa fa-cubes' }} font-dark"></i>
            <span class="caption-subject font-dark"> {{ $title }}</span>
          </div>
          <div class="actions btn-set">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="method" value="{{ $method }}">
            @if ($data)
            <input type="hidden" name="__" value="{{ $data['__'] }}">
            @endif
            <button type="button" ui-sref="system_cache" name="back" class="btn btn-secondary-outline"><i class="fa fa-angle-left"></i> {{ trans('ui::form.button.back') }} </button>
            <button type="reset" class="btn btn-secondary-outline"><i class="fa fa-reply"></i> {{ trans('ui::form.button.reset') }} </button>
            <button type="submit" class="btn green mt-ladda-btn ladda-button" data-style="expand-left" data-spinner-color="#fff">
              <span class="ladda-label">
                {{ trans('ui::form.button.submit') }} 
              </span>
            </button>
          </div>
        </div>
        <div class="portlet-body">
          <div class="tabbable-line">
            <ul class="nav nav-tabs nav-tabs-lg">
              @foreach ($sections as $slug => $section)
              <li class="{{ $section == reset($sections) ? 'active' : '' }}"><a href="javascript:;" data-target="#tab_{{ $slug }}" data-toggle="tab"> {{ $section['label'] }} </a></li>
              @endforeach
            </ul>
            <div class="tab-content">
              @foreach ($sections as $slug => $section)
              <div class="tab-pane {{ $section == reset($sections) ? 'active' : '' }}" id="tab_{{ $slug }}">
                @foreach ($section['elements'] as $element)
                @include($element['template'], $element)
                @endforeach
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </form>
    {{-- End: DataForm --}}
  </div>
</div>
