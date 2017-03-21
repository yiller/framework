<div class="row">
  <div class="col-md-12">
    <form action="{{ $handle_url }}" method="post" class="form-horizontal form-ajax" enctype="multipart/form-data" role="form">
      <div class="portlet light portlet-fit bordered">
        <div class="portlet-title">
          <div class="caption">
            <i class="icon-cloud-upload font-dark"></i>
            <span class="caption-subject font-dark">{{ $title }}</span>
          </div>
        </div>
        <div class="portlet-body">
          <div class="mt-element-step">
            <div class="row step-thin">
              @foreach ($steps as $i => $step)
              <div class="col-md-{{ $col }} bg-grey mt-step-col 
                {{ $current == $i ? 'active' : ($current > $i ? 'done' : '') }}">
                <div class="mt-step-number bg-white">{{ $i+1 }}</div>
                <div class="mt-step-title font-grey-cascade">{{ $step[0] }}</div>
                <div class="mt-step-content font-grey-cascade">{{ $step[1] }}</div>
              </div>
              @endforeach
            </div>
          </div>
          <div class="form">
            <div class="form-body">@yield('content')</div>
            <div class="form-actions">
              <div class="row">
                <div class="col-md-6"><a href="javascript:;" class="btn btn-outline grey-salsa disabled pull-right">上一步：开始导入</a></div>
                <div class="col-md-6"><a href="javascript:;" class="btn green">下一步：文件分析</a></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>