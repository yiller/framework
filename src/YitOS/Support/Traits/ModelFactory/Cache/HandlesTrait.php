<?php namespace YitOS\Support\Traits\ModelFactory\Cache;

use Illuminate\Http\Request;
use YitOS\Support\Facades\WebSocket;
use YitOS\Support\Traits\BootstrapUI\DataHandlesTrait;

/**
 * 数据缓存管理控制器分离类（数据处理）
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\ModelFactory\Cache
 * @see \YitOS\Support\Traits\ModelFactory\Cache\Config
 * @see \YitOS\Support\Traits\BootstrapUI\DataHandlesTrait
 * @see \YitOS\Support\Traits\BootstrapUI\CommonTrait
 */
trait HandlesTrait {
  use Config, DataHandlesTrait;
  
  /**
   * 表单项额外配置（结构树和数据表）
   * @access protected
   * @param array $columns
   * @return array
   */
  protected function columnsConfigured($columns) {
    $columns['elements'] = ['name' => 'elements', 'label' => '结构配置', 'bind' => 'elements', 'type' => 'json', 'multi_language' => false, 'section' => '数据缓存'];
    $columns = array_only_by_sort($columns, ['name','alias','model','elements','duration']);
    $options = [
      'name' => ['section' => '数据缓存', 'extra' => ['class' => 'input-small'], 'helper' => '数据名称由4位字符组成，可以包含中文', 'rules' => 'bail|required|min:4', 'messages' => ['name.required' => '请输入数据名称', 'name.min' => '数据名称最少不低于4个字']],
      'alias' => ['section' => '数据缓存', 'extra' => ['class' => 'input-small'], 'helper' => '数据别名由6位字母、数字、下划线和中横线组成', 'rules' => 'bail|required|alpha_dash|min:6', 'messages' => ['alias.required' => '请输入数据别名', 'alias.alpha_dash' => '数据别名只能由字母、数字、下划线和中横线组成', 'alias.min' => '数据别名最少不低于6个字符']],
      'model' => ['section' => '数据缓存', 'helper' => '在明白该值含义之前，请勿随便填写', 'rules' => 'required|class_exists', 'messages' => ['model.required' => '请输入映射模型', 'model.class_exists' => '请输入有效的模型路径']],
      'duration' => ['section' => '数据缓存', 'template' => 'system.cache.form.duration'],
    ];
    return array_replace_recursive($columns, $options);
  }
  
  /**
   * 数据缓存表单项配置
   * @access protected
   * @return array
   */
  protected function getElementsItems() {
    return [['name' => 'name', 'label' => '名称', 'type' => 'string'],['name' => 'alias', 'label' => '别名', 'type' => 'slug'],['name' => 'structure', 'label' => '数据类型', 'type' => 'select'],['name' => 'multi_language', 'label' => '是否多语言', 'type' => 'boolean']];
  }
  
  /**
   * 允许的数据结构类型
   * @access protected
   * @return array
   */
  protected function getStructureOptions() {
    return [['label' => '字符型', 'value' => 'string'],['label' => '标签型', 'value' => 'slug'],['label' => '图标型', 'value' => 'icon'],['label' => '长字符型', 'value' => 'text'],['label' => '字母型', 'value' => 'alpha'],['label' => '字母数字型', 'value' => 'alpha_num'],['label' => '超文本标记', 'value' => 'html'],['label' => '整数型', 'value' => 'integer'],['label' => '小数型', 'value' => 'decimal'],['label' => '布尔型', 'value' => 'boolean'],['label' => '数组型', 'value' => 'json'],['label' => '日期型', 'value' => 'date'],['label' => '时间型', 'value' => 'time'],['label' => '日期时间型', 'value' => 'datetime'],['label' => 'UNIX时间戳', 'value' => 'timestamp'],['label' => '图片上传', 'value' => 'pictures'],['label' => 'URL地址', 'value' => 'url'],['label' => 'SEO三要素', 'value' => 'TKD']];
  }
  
  /**
   * 获得数据缓存保存时的提交数据
   * @access public
   * @return array
   */
  protected function getSaveData() {
    $data = app('request')->all();
    $languages = app('auth')->user()->team['languages'];
    $data['elements'] = isset($data['elements']) && $data['elements'] && is_array($data['elements']) ? $data['elements'] : [[]];
    foreach ($data['elements'] as $key => $val) {
      $val['name'] = isset($val['name']) && $val['name'] && is_string($val['name']) ? $val['name'] : '';
      $val['alias'] = isset($val['alias']) && $val['alias'] && is_string($val['alias']) ? $val['alias'] : '';
      $val['structure'] = isset($val['structure']) && $val['structure'] && is_string($val['structure']) ? $val['structure'] : '';
      $val['multi_language'] = isset($val['multi_language']) ? ($languages ? boolval($val['multi_language']) : false) : false;
      $data['elements'][$key] = $val;
    }
    $data['enabled_sync'] = isset($data['enabled_sync']);
    $data['duration'] = $data['enabled_sync'] && isset($data['duration']) ? $data['duration'] : 0;
    $data['built_in'] = false;
    return $data;
  }
  
  /**
   * 获得数据缓存保存时的表单验证器
   * @access protected
   * @param array $rules
   * @param array $messages
   * @return \Illuminate\Validation\Validator
   */
  protected function getSaveValidator(array $data, array $rules, array $messages) {
    $types = [];
    foreach ($this->getStructureOptions() as $val) $types[] = $val['value'];
    $rules['elements.*.name'] = 'bail|required|min:2';
    $messages['elements.*.name.required'] = '请输入名称';
    $messages['elements.*.name.min'] = '名称最少不低于2个字';
    $rules['elements.*.alias'] = 'bail|required|alpha_dash|min:4';
    $messages['elements.*.alias.required'] = '请输入别名';
    $messages['elements.*.alias.alpha_dash'] = '别名只能由字母、数字、下划线和中横线组成';
    $messages['elements.*.alias.min'] = '别名最少不低于6个字符';
    $rules['elements.*.structure'] = 'bail|required|in:'.implode(',',$types);
    $messages['elements.*.structure.required'] = '请选择数据类型';
    $messages['elements.*.structure.in'] = '非法的数据类型';
    $rules['alias'] .= '|unique:_meta,alias';
    $messages['alias.unique'] = '数据别名已存在';
    
    $messages['duration.required'] = '请输入同步时间间隔';
    $messages['duration.integer'] = '同步时间间隔必须是整数';
    $messages['duration.min'] = '同步时间间隔应该不小于 10 秒';
    $validator = \Illuminate\Support\Facades\Validator::make($data, $rules, $messages);
    
    $validator->sometimes('duration', 'required|integer|min:10', function($input) {
      return isset($input->enabled_sync) && $input->enabled_sync;
    });
    
    return $validator;
  }
  
  /**
   * 保存数据
   * @access protected
   * @param array $data
   * @return bool
   * 
   * @throws \Illuminate\Foundation\Validation\ValidationException
   */
  protected function handleSave(array $data) {
    $classname = $data['model'];
    $duration = intval($data['duration']);
    $response = WebSocket::{'sync/install'}($data);
    if (!$response || $response['code'] != 1) {
      return false;
    }
    extract($response);
    foreach ($elements as $key => $element) {
      $element['multi_language'] = boolval($element['multi_language']);
      $elements[$key] = $element;
    }
    $data = [
      'name'     => $entity['name'],
      'alias'    => $entity['alias'],
      'model'    => $classname,
      'built_in' => !$entity['account_id'],
      'elements' => $elements,
      'duration' => $duration,
      'synchronized_at' => 0
    ];
    $this->builder()->insert($data);
    return true;
  }
  
  /**
   * 重建缓存（GET）
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function rebuild(Request $request) {
    $data = ['handle_url' => $this->handle_url,'icon' => 'fa fa-refresh','title' => '重建'.$this->name,'method' => 'rebuild'];
    $__ = $request->input('__', '');
    $model = $this->builder()->find($__);
    if ($model) {
      $model['__'] = $__;
      unset($model['_id']);
      $data['data'] = $model;
      $data['content'] = $model['duration'] > 0 ? '缓存重建将重新计算同步时间，并重新缓存数据。' : '缓存重建将清除现有的全部数据，且无法恢复。';
      $data['content'] .= '你确定要进行该项操作？';
      $data['enabled'] = true;
    } else {
      $data['data'] = [];
      $data['content'] = '没有找到对应的缓存数据结构，请关闭本窗口。';
      $data['enabled'] = false;
    }
    return view('ui::modal.basic', $data);
  }
  
  /**
   * 重建缓存（POST）
   * @access protected
   * @param array $data
   * @return bool
   */
  protected function handleRebuild(array $data) {
    $__ = isset($data['__']) ? $data['__'] : '';
    $model = $this->builder()->find($__);
    if (!$model) return false;
    $model['synchronized_at'] = 0;
    unset($model['_id']);
    $this->builder()->update(['synchronized_at' => 0], ['_id' => $__]);
    
    $class = $model['model'];
    $class::truncate();
    
    M($model['alias']);
    
    return true;
  }
  
  /**
   * 数据导入
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Routing\Redirector
   */
  public function import(Request $request) {
    $__ = $request->input('__', '');
    $model = $this->builder()->find($__);
    if (!$model) abort(404);
    
    if (app('routes')->hasNamedRoute($model['alias'].'.import')) {
      $url = route($model['alias'].'import');
    } else {
      $url = route('data.import').'?__='.$__;
    }
    
    return redirect($url);
  }
  
  /**
   * 数据导出
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Routing\Redirector
   */
  public function export(Request $request) {
    $__ = $request->input('__', '');
    $model = $this->builder()->find($__);
    if (!$model) abort(404);
    
    if (app('routes')->hasNamedRoute($model['alias'].'.export')) {
      $url = route($model['alias'].'export');
    } else {
      $url = route('data.export').'?__='.$__;
    }
    
    return redirect($url);
  }
  
}
