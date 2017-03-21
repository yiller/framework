<div class="row">
  <div class="col-md-12">
    {{-- Begin: Datatable --}}
    <div class="portlet light portlet-fit portlet-datatable bordered">
      <div class="portlet-title">
        <div class="caption">
          <i class="fa fa-bars font-dark"></i>
          <span class="caption-subject font-dark">{{ trans('ui.datatable.title', compact('name')) }}</span>
        </div>
        @if ($enabled_add)
        <div class="actions">
          <a href="{{ $add_url }}" class="btn btn-outline green">
            <i class="fa fa-plus"></i>
            <span class="hidden-xs"> {{ trans('ui.datahandle.button.add', compact('name')) }} </span>
          </a>
        </div>
        @endif
      </div>
      <div class="portlet-body">
        <div id="ajax-modal" class="modal fade modal-scroll" tabindex="-1"> </div>
        <div class="table-container">
          <table class="table table-striped table-bordered table-hover table-checkable" id="datatable_ajax">
            <thead>
              <tr role="row" class="heading">
                <th width="2%"></th>
                @foreach ($columns as $column)
                <th width="{{ $column['width'] or '' }}"><span style="display:block;text-align:{{ $column['align'] or 'left' }};"> {{ $column['label'] }} </span></th>
                @endforeach
                @if ($enabled_search || $enabled_handles)
                <th> {{ trans('ui.datahandle.text.actions') }} </th>
                @endif
              </tr>
              @if ($enabled_search)
              <tr role="row" class="filter"></tr>
              @endif
            </thead>
            <tbody> </tbody>
          </table>
        </div>
      </div>
    </div>
    {{-- End: Datatable --}}
  </div>
</div>

<script type="text/javascript">
TableDatatablesAjax.init("{{ csrf_token() }}", {
  loadingMessage: "{{ trans('ui.datahandle.text.loading') }}",
  dataTable: {
    "language": {
      "loadingRecords": "{{ trans('ui.datahandle.text.loading') }}",
      "metronicAjaxRequestGeneralError": "{{ trans('ui.datahandle.exception.internet') }}",
      "emptyTable": "{{ trans('ui.datahandle.text.zeroRecords', ['name' => $name]) }}",
      "zeroRecords": "{{ trans('ui.datahandle.text.zeroRecords', ['name' => $name]) }}",
    },
    "ajax": {
      "url": "{{ $data_url }}",
    }
  }
});
</script>