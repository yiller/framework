<?php namespace YitOS\Support\Traits\BootstrapUI;

use RuntimeException;
use Illuminate\Http\Request;

/**
 * 数据处理分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\BootstrapUI
 */
trait DataHandlesTrait {
  use CommonTrait;
  
  /**
   * 数据处理控制器构造函数
   * @access public
   * @return void
   * 
   * @throws RuntimeException
   */
  protected function initial() {
    // 数据处理入口
    if (!property_exists($this, 'handleUrl') || !$this->handle_url) {
      $this->handle_url = action('\\'.get_class($this).'@handle');
    }
    
    $sections = [];
    foreach ($this->columns as $key => $element) {
      if (!isset($element['section'])) continue;
      $element = $this->formatElementForHandle($element);
      $label = $element['section'];
      $slug = substr(md5($label),0,5);
      isset($sections[$slug]) || $sections[$slug] = ['label' => $label, 'elements' => []];
      unset($element['section']);
      $sections[$slug]['elements'][$key] = $element;
    }
    $this->sections = $sections;
    if (!$this->sections) {
      throw new RuntimeException(trans('ui::exception.handles_not_supported'));
    }
    method_exists($this, 'customize') && $this->customize();
  }
  
  /**
   * 为表单渲染规范元素定义
   * @access protected
   * @param array $element
   * @return array
   */
  protected function formatElementForHandle($element) {
    // 元素类型规范
    $method = 'get'.studly_case($element['type']).'Element';
    $method = method_exists($this, $method) ? $method : 'getDefaultElement';
    $element = $this->$method($element);
    // 元素额外样式和属性的定义
    $element['extra'] = isset($element['extra']) && $element['extra'] && is_array($element['extra']) ? $element['extra'] : [];
    // 元素模板
    $template = isset($element['template']) && $element['template'] && is_string($element['template']) ? $element['template'] : 'ui::form.'.$element['type'];
    view()->exists($template) || $template = 'ui::form.string';
    $element['template'] = $template;
    return $element;
  }
  
  /**
   * 获得父级元素选择的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getParentIdElement($element) {
    if (!isset($element['placeholder']) || !is_string($element['placeholder'])) {
      $element['placeholder'] = '';
    }
    $element['type'] = 'select';
    
    $builder = method_exists($this, 'parentIdElementRetrieving') ? $this->parentIdElementRetrieving() : $this->definedBuilder();
    
    $children = function($id) use($builder, &$children) {
      $arr = [];
      $nodes = $builder->where('parent_id', $id)->orderBy('sort_order', 'asc')->orderBy('updated_at', 'desc')->get();
      foreach ($nodes as $node) {
        $content = "<span style='display:inline-block;text-indent:".count($node->parents)."em;'>".$node->label."</span>";
        $item = ['label' => $node->label, 'value' => $node->getKey(), 'content' => $content];
        $arr[] = $item;
        $arr = array_merge($arr, $children($node->getKey()));
        $node->parent_id == 0 && $arr[] = ['divider' => 1];
      }
      return $arr;
    };
    $element['options'] = $children(isset($element['parent_id']) ? $element['parent_id'] : '');
    array_unshift($element['options'], ['divider' => 1]);
    return $this->getSelectElement($element);
  }
  
  /**
   * 获得JSON的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getJsonElement($element) {
    $method = isset($element['items_getter']) && $element['items_getter'] && is_string($element['items_getter']) ? $element['items_getter'] : 'get'.studly_case($element['name']).'Items';
    if (isset($element['items']) && is_array($element['items'])) {
      $items = $element['items'];
    } elseif (method_exists($this, $method)) {
      $items = $this->$method();
    } else {
      $items = [];
    }
    $element['items'] = [];
    foreach ($items as $item) $element['items'][$item['name']] = $this->formatElementForHandle($item);
    $element['header'] = $element['line'] = null;
    return $element;
  }
  
  /**
   * 获得多图上传的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getPicturesElement($element) {
    $width = isset($element['width']) ? intval($element['width']) : 150;
    $max = 350; $scale = 1;
    if ($width <= $max) {
      $scale = 1;
    } elseif (floor($width / 2) <= $max) {
      $scale = 2;
    } elseif (floor($width / 3) <= $max) {
      $scale = 3;
    } else {
      $scale = 6;
    }
    $height = isset($element['height']) ? intval($element['height']) : 0;
    $height = $height > 0 ? $height : $width;
    $link_enabled = isset($element['link_enabled']) && $element['link_enabled'];
    
    $header_render = function() use($link_enabled) {
      $html = '<th width="25%">图片上传</th>';
      if ($link_enabled) {
        $html .= '<th width="25%">链接地址</th>';
      }
      $html .= '<th width="25%">图片描述</th>';
      return $html;
    };
    
    $line_render = function($item) use($width, $height, $scale, $link_enabled) {
      $cell_width = floor($width / $scale);
      $cell_height = floor($height / $scale);
      if ($item) {
        $html = '<td><div class="fileinput fileinput-preview" data-provides="fileinput">';
      } else {
        $html = '<td><div class="fileinput fileinput-new" data-provides="fileinput">';
      }
      $html .= '<div class="fileinput-new thumbnail" style="width:'.($cell_width+10).'px; height:'.($cell_height+10).'px;">';
      $html .= '<img src="http://www.placehold.it/'.$cell_width.'x'.$cell_height.'/EFEFEF/AAAAAA&amp;text='.$width.'x'.$height.'" alt="">';
      $html .= '</div>';
      $html .= '<div class="fileinput-preview fileinput-exists thumbnail" style="width:'.($cell_width+10).'px; height:'.($cell_height+10).'px;">';
      if ($item) {
        $html .= '<img src="'.$item['src'].'" />';
      }
      $html .= '</div>';
      $html .= '<div><span class="btn default btn-file">';
      $html .= '<span class="fileinput-new"> 选择图片 </span>';
      $html .= '<span class="fileinput-exists"> 重新上传 </span>';
      $html .= '<input type="file" name="src">';
      $html .= '</span><a href="javascript:;" class="btn red fileinput-exists" data-dismiss="fileinput"> 删除图片 </a></div>';
      $html .= '</div></td>';
      if ($link_enabled) {
        $html .= '<td><input name="link" type="text" placeholder="链接地址" class="form-control" value="'.($item?$item['link']:'').'" /></td>';
      }
      $html .= '<td><input name="alt" type="text" placeholder="图片描述" class="form-control" value="'.($item?$item['alt']:'').'" /></td>';
      return $html;
    };
    
    $element['type'] = 'repeat';
    $element['header'] = $header_render;
    $element['line'] = $line_render;
    return $element;
  }
  
  /**
   * 获得布尔值的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getBooleanElement($element) {
    $element['options'] = ['否', '是'];
    return $this->getRadioElement($element);
  }
  
  /**
   * 获得单选的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getRadioElement($element) {
    $element['options'] = isset($element['options']) && $element['options'] && is_array($element['options']) ? $element['options'] : ['否', '是'];
    $options = [];
    foreach ($element['options'] as $key => $val) $options[] = ['label' => $val, 'value' => $key];
    $element['default'] = isset($element['default']) && array_key_exists($element['default'], $element['options']) ? $element['default'] : array_keys($element['options'])[0];
    $element['options'] = $options;
    return $element;
  }
  
  /**
   * 获得下拉菜单的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getSelectElement($element) {
    if (isset($element['options']) && is_array($element['options'])) {
      $options = $element['options'];
    } else {
      $getter = isset($element['options_getter']) ? $element['options_getter'] : '';
      if ($getter && !is_string($getter)) {
        $options = $getter();
      } else {
        $method = $getter ?: 'get'.studly_case($element['name']).'Options';
        $options = method_exists($this, $method) ? $this->$method() : [];
      }
    }
    if (!isset($element['required']) || !$element['required']) {
      $placeholder = (isset($element['placeholder']) && is_string($element['placeholder'])) ? $element['placeholder'] : trans('ui::form.select.placeholder');
      array_unshift($options, ['label' => $placeholder, 'value' => '']);
    }
    $element['options'] = $options;
    return $element;
  }
  
  /**
   * 获得默认元素的配置
   * @access protected
   * @param array $element
   * @return array
   */
  protected function getDefaultElement($element) {
    if (!array_key_exists('placeholder', $element) || !is_string($element['placeholder'])) {
      $element['placeholder'] = $element['label'];
    }
    return $element;
  }
  
  /**
   * 显示编辑表单
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request) {
    $template = 'ui::form.layout';
    property_exists($this, 'template') && view()->exists($this->template) && $template = $this->template;
    $data = [
      'handle_url' => $this->handle_url,
      'sections' => $this->sections,
      'method' => 'save',
      'data' => []
    ];
    if ($data['data']) {
      $data['title'] = trans('ui::form.modal.title_edit', ['name' => $this->name, '__' => $__]);
    } else {
      $data['title'] = trans('ui::form.modal.title_create', ['name' => $this->name]);
    }
    return view($template, $data);
    
    /*$data = method_exists($this, 'formRenderring') ? $this->formRenderring($this->configDF()) : $this->configDF();
    array_key_exists('modal_title_icon', $data) || $data['modal_title_icon'] = 'fa fa-cubes';
    $template = array_key_exists('template', $data) && View::exists($data['template']) ? $data['template'] : 'ui::form.layout';
    $data['data'] = [];
    $__ = $request->get('__');
    if ($__) {
      $builder = $this->definedBuilder();
      if ($builder instanceof \YitOS\ModelFactory\Factories\Factory) {
        $model = $builder->find($__);
        $data['data'] = $model ? $model->toArray() : [];
      } else {
        $object = $builder->where('_id', $__)->first();
        $data['data'] = $object ? (array)$object : [];
      }
    }
    if ($data['data']) {
      array_key_exists('modal_title', $data) || $data['modal_title'] = trans('ui::form.modal.title_edit', ['name' => $data['name'], '__' => $__]);
    } else {
      array_key_exists('modal_title', $data) || $data['modal_title'] = trans('ui::form.modal.title_create', ['name' => $data['name']]);
    }
    $data['method'] = 'save';
    return view($template, $data);*/
  }
  
  /**
   * 保存数据
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   * 
   * @throws RuntimeException
   */
  public function handle(Request $request) {
    $method = $request->has('method') ? $request->get('method') : 'save';
    $enabled = false;
    if ($method == 'save') {
      $enabled = $request->has('__') ? array_key_exists('edit', $this->handles) : $this->enabled_add;
    } else {
      $enabled = array_key_exists($method, $this->handles);
    }
    if (!$enabled || !method_exists($this, 'handle'.studly_case($method))) {
      if (($request->ajax() && ! $request->pjax()) || $request->wantsJson()) {
        return response()->json(['message' => trans('ui::exception.handles.handle_not_supported')], 405);
      } else {
        throw new RuntimeException(trans('ui::exception.handles.handle_not_supported'));
      }
    }
    
    $data = method_exists($this, 'get'.studly_case($method).'Data') ? $this->{'get'.studly_case($method).'Data'}() : $request->all();
    if ($method == 'save') {
      extract($this->getColumnsRules());
      $validator = method_exists($this, 'getSaveValidator') ? $this->getSaveValidator($data, $rules, $messages) : ($rules ? \Illuminate\Support\Facades\Validator::make($data, $rules, $messages) : null);
    } else {
      $validator = method_exists($this, 'get'.studly_case($method).'Validator') ? $this->{'get'.studly_case($method).'Validator'}($data) : null;
    }
    $validator && $this->validateWith($validator);
    unset($data['_token'], $data['method']);
    // $config = method_exists($this, 'formRenderring') ? $this->formRenderring($this->configDF()) : $this->configDF();
    if ($this->{'handle'.studly_case($method)}($data)) {
      $message = property_exists($this, 'handle_'.$method.'_success') ? $this->{'handle_'.$method.'_success'} : trans('ui::form.handle.'.$method.'_success', ['name' => $this->name]);
      $status = 1;
    } else {
      $message = property_exists($this, 'handle_'.$method.'_success') ? $this->{'handle_'.$method.'_failure'} : trans('ui::form.handle.'.$method.'_fail', ['name' => $this->name]);
      $status = 0;
    }
    return response()->json(compact('status', 'message'));
  }
  
  /**
   * 获得列定义的数据规则
   * @access protected
   * @return array
   */
  protected function getColumnsRules() {
    $rules = $messages = [];
    foreach ($this->columns as $key => $column) {
      if (!isset($column['rules'])) continue;
      $rule = $message = [];
      if (is_string($column['rules'])) {
        $rule[$key] = $column['rules'];
      } elseif (is_array($column['rules'])) {
        $rule = $column['rules'];
      }
      if (isset($column['messages'])) {
        if (is_string($column['messages']) && is_string($column['rules'])) {
          $k = (false === ($pos = strpos($column['rules'], ':'))) ? $column['rules'] : substr($column['rules'],0,$pos);
          $message[$key.'.'.$k] = $column['messages'];
        } elseif (is_array($column['messages'])) {
          $message = $column['messages'];
        }
      }
      if (!$rule) continue;
      $rules = array_merge($rules, $rule);
      $messages = array_merge($messages, $message);
    }
    return compact('rules', 'messages');
  }
  
  /**
   * 保存数据
   * @access protected
   * @param array $data
   * @return mixed
   */
  protected function handleSave(array $data) {
    $builder = $this->builder();
    if ($builder instanceof \Illuminate\Database\Query\Builder) {
      $__ = isset($data['__']) ? $data['__'] : '';
      if ($__) {
        $__ = $builder->update(['_id' => $__], $data) ? $__ : '';
      } else {
        $__ = $builder->insertGetId($data);
      }
      dd($__);
    } else {
      $model = $this->builder()->save($data);
      dd($model);
    }
    // 处理数据保存
    /*$__ = '';
    $model = $this->builder()->save($data);
    dd($model);
    if ($model) {
      $__ = $model->_id;
      if ($model->parents) {
        $builder->where('children', 'all', [$__])->pull('children', $__);
        $builder->whereIn('_id', $model->parents)->push('children', $__, true);
      }
      if (method_exists($builder->model(), 'modelSaved')) {
        return $builder->model()->modelSaved($__);
      }
    }
    return $__;*/
    
      /*$model = null;
      if (isset($data['__']) && ($model = $builder->find($data['__']))) {
        unset($data['__']);
        $model = $model->fill($data);
      } else {
        unset($data['__']);
        $model = $builder->model()->fill($data);
      }*/
  }
  
}
