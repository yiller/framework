var ExternalSynchronize = function() {

  var $_table = null;

  // AJAX远程调用
  var RPC = function(params, handle_callback, handle_error) {
    var url = '{{ $url }}';
    if (params._token == undefined) {
      params._token = '{{ csrf_token() }}';
    }
    $.post(url, params, function(data) {
      if (data.handle != undefined) {
        eval(data.handle);
        return;
      }
      enable_buttons();
      if (handle_callback != null) handle_callback(data);
    }).error(function() {
      enable_buttons();
      if (handle_error != null) handle_error();
    });
  };
  // 启用对话框底部按钮
  var enable_buttons = function() {
    var cancel = $('#ajax-modal .modal-footer button[type=button]');
    submit.stop();
    cancel.prop('disabled', false);
    grid.getDataTable().ajax.reload();
  };
  // 禁用对话框底部按钮
  var disable_buttons = function(disabled) {
    var cancel = $('#ajax-modal .modal-footer button[type=button]');
    submit.start();
    cancel.prop('disabled', true);
  };
  // 构建表格头部
  var table_head = function(cells) {
    var $thead = $('thead tr:first', $_table);
    $thead.find('th:eq(0)').siblings().remove();
    for (var i in cells) {
      var cell = cells[i];
      $('<th></th>').attr('width', cell.width).html(cell.text).appendTo($thead);
    }
  };
  // 增加行
  var table_line = function(id, status, cells) {
    var $icon = $('<i></i>');
    var $line = $('<tr></tr>').attr('id', id);
    if (status == 'success') {
      $icon.attr('class', 'icon-check');
    } else if (status == 'danger') {
      $icon.attr('class', 'icon-close');
    } else {
      status = '';
      $icon.attr('class', 'fa fa-spin fa-spinner');
    }
    $line.addClass(status);
    $('<td></td>').attr('align', 'center').append($icon).appendTo($line);
    for (var i in cells) {
      var cell = cells[i];
      $('<td></td>').html(cell).appendTo($line);
    }
    $('tbody', $_table).prepend($line);
  };
  // 行状态变更
  var line_status = function(id, status, cells) {
    var $line = $('tbody tr:first', $_table);
    if (id != '') {
      $line = $('#'+id);
    }
    var $icon = $line.find('td:eq(0) i:first');
    if (status == 'success') {
      $icon.attr('class', 'icon-check');
    } else if (status == 'warning') {
      $icon.attr('class', 'fa fa-warning');
    } else if (status == 'danger') {
      $icon.attr('class', 'icon-close');
    } else {
      status = '';
      $icon.attr('class', 'fa fa-spin fa-spinner');
    }
    $line.addClass(status);
    for (var i = 1; i <= cells.length; i++) {
      var cell = cells[i-1];
      if (cell == '' || cell == null) continue;
      $line.find('td:eq('+i+')').html(cell);
    }
  };
  var modal_layout = function() {
    $modal.css('margin-top', 0 - $modal.height() / 2).removeClass('modal-overflow');
  };
  // 远程抓取JS桥
  var FETCH = function(params) {
    var error = function() {
      line_status('', 'danger', ['', '', '', '未知错误']);
    };
    var callback = function(data) {
      var message = '同步成功';
      var proc = '';
      var status = 'success';
      if (data.status != 1) {
        proc = data.message == undefined ? '未知错误' : data.message;
        message = '同步失败';
        status = 'danger';
      }
      line_status('', status, ['', '', '', proc]);
    };
    window.setTimeout(function() {
      RPC(params, callback, error);
    }, 1000);
  };
  // 初始化调用
  var initial = function() {
    var params = {'step': 'initial'};
    params.handle = '{{ $handle }}';
    params.__ = '{{ $model->_id }}';
    
    if ($('#ajax-modal .modal-body table').size() == 0) {
      $('#ajax-modal .modal-body').append('<table class="table table-striped table-bordered table-hover"><thead><tr role="row" class="heading"><th width="3%"></th></tr></thead><tbody></tbody></table>');
    }
    $_table = $('#ajax-modal .modal-body table:first');
    $('tbody', $_table).empty();
    
    disable_buttons();
    RPC(params, function(data) {
      var message = '远程数据同步失败，请稍后重试！';
      if (data.status != 1 && data.message != undefined) {
        message = data.message;
      }
      message = ' ' + message + ' <button type="button" class="close" data-dismiss="alert">&times;</button>';
      if ($body.find('>.alert.alert-danger').size() > 0) {
        $body.find('>.alert.alert-danger').html(message);
      } else {
        $body.prepend('<div class="alert alert-danger fade in">' + message + '</div>');
      }
      $body.animate({scrollTop:0},600);
    }, function() {
      var message = '远程数据同步失败，请稍后重试！';
      message = ' ' + message + ' <button type="button" class="close" data-dismiss="alert">&times;</button>';
      if ($body.find('>.alert.alert-danger').size() > 0) {
        $body.find('>.alert.alert-danger').html(message);
      } else {
        $body.prepend('<div class="alert alert-danger fade in">' + message + '</div>');
      }
      $body.animate({scrollTop:0},600);
    });
  };
  // 远程获得分类列表
  var listings = function(id, page) {
    FETCH({
      'step': 'listings',
      'handle': 'listings',
      '__': id,
      'page': parseInt(page)
    });
  };
  // 远程获得详情
  var detail = function() {
    var params = {'step':'detail','handle':'detail'};
    if (arguments.length > 0) {
      params.__ = arguments[0];
    }
    if (arguments.length > 1) {
      params.step = arguments[1];
    }
    FETCH(params);
  };
  
  return {
    start: function() { initial(); }
  };
  
}();

ExternalSynchronize.start();