<div class="row">
  <div class="col-md-12">
    {{-- Begin: Datatree --}}
    <div class="portlet light portlet-fit bordered">
      <div class="portlet-title">
        <div class="caption">
          <i class="fa fa-sitemap font-dark"></i>
          <span class="caption-subject font-dark">{{ trans('ui.datatree.title', compact('name')) }}</span>
        </div>
        <div class="actions">
          @if ($add_enabled)
          <a data-url="{{ $add_url }}" data-toggle="modal" data-width="full" data-static="true" class="btn purple-sharp btn-outline modal-toggler">
            <i class="fa fa-plus"></i>
            <span class="hidden-xs"> {{ trans('ui.datahandle.button.add', compact('name')) }} </span>
          </a>
          @endif
        </div>
      </div>
      <div class="portlet-body">
        <div id="ajax-modal" class="modal fade modal-scroll" tabindex="-1"> </div>
        <div class="table-container">
          <table class="table table-striped table-bordered table-hover dt-responsive" id="datatree_ajax">
            <thead>
              <tr role="row" class="heading">
                <th width="4%"></th>
                @foreach ($columns as $column)
                <th width="{{ $column['width'] or '' }}"> {{ $column['label'] }} </th>
                @endforeach
                @if ($handle_enabled)
                <th width="150"> {{ trans('ui.datahandle.text.actions') }} </th>
                @endif
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
    {{-- End: Datatree --}}
  </div>
</div>

<script type="text/javascript">
TableDatatreeAjax.init("{{ csrf_token() }}", {
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