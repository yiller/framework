@extends('ui::core.wizard')

@section('content')
<div class="row">
  <div class="col-md-6">
    <div class="portlet light bordered">
      <div class="portlet-title">
        <div class="caption">
          <i class="icon-cloud-upload"></i>
          <span class="caption-subject"> 数据文件上传</span>
        </div>
      </div>
      <div class="portlet-body">
        <p>正常情况下，系统识别 Microsoft Office Excel 2007 或者更新版本保存的文件格式（.xlsx）文件，但是如果系统不能识别你上传的表格文件请尝试另存为 .xls 格式后再进行上传（表格文件需要有表头）</p>
        <div class="form-group" style="margin-bottom:9px;">
          <label class="control-label col-md-3">表格文件</label>
                                                <div class="col-md-9">
                                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                                        <div class="input-group input-large">
                                                          <div class="form-control uneditable-input input-fixed input-medium" data-trigger="fileinput">
                                                                <span class="fileinput-filename"> </span>
                                                            </div>
                                                            <span class="input-group-addon btn default btn-file">
                                                              <span class="fileinput-new"><i class="fa fa-cloud-upload"></i> 文件上传 </span>
                                                                <span class="fileinput-exists"> 重新选择 </span>
                                                                <input type="file" name="..."> </span>
                                                            <a href="javascript:;" class="input-group-addon btn red fileinput-exists" data-dismiss="fileinput"> 删除 </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="portlet light bordered">
      <div class="portlet-title">
        <div class="caption">
          <i class="icon-cloud-download"></i>
          <span class="caption-subject"> 示例文件下载</span>
        </div>
      </div>
      <div class="portlet-body">
        <p>在不确定数据结构的情况下，强烈建议你先下载示例文件，示例文件表格中包含了结构应有的表头，表头列顺序并不重要，但是建议每行数据都有对应的列值（表头数据请勿删除）</p>
        <div class="form-group">
          <div class="col-md-12">
            <a href="" class="btn dark"><i class="fa fa-cloud-download"></i> 示例文件下载 </a>
          </div>
        </div>
        
      </div>
    </div>
  </div>
</div>
@endsection