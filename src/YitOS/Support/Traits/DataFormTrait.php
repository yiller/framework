<?php namespace YitOS\Support\Traits;

use RuntimeException;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;

/**
 * 数据表单分离类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits
 */
trait DataFormTrait {
  
  /**
   * 加载配置
   * 
   * @access protected
   * @return array
   * 
   * @throws RuntimeException
   */
  protected function configDF() {
    if (!property_exists($this, 'name') || !property_exists($this, 'route') || 
        !method_exists($this, 'definedBuilder') || !method_exists($this, 'definedElements') || 
        !property_exists($this, 'elements') || !is_array($this->elements) || empty($this->elements)) {
      throw new RuntimeException(trans('ui::exception.form_not_supported'));
    }
    $config = [];
    $config['name'] = $this->name;
    if (!(app('routes')->hasNamedRoute($this->route.'.handle'))) {
      throw new RuntimeException(trans('ui::exception.form_not_supported'));
    }
    $config['handle_url'] = route($this->route.'.handle');
    
    $elements = [];
    foreach ($this->elements as $key => $element) {
      $element['name'] = $key;
      if (!isset($element['bind'])  || !is_string($element['bind'])) { $element['bind']  = $key; }
      $elements[] = $element['bind'];
      $this->elements[$key] = $element;
    }
    $elements = array_only_by_sort($this->definedElements(), $elements);
    if (empty($elements)) {
      throw new RuntimeException(trans('ui::exception.form_not_supported'));
    }
    $config['sections'] = [];
    $sections = property_exists($this, 'sections') ? $this->sections : [['label' => '', 'elements' => array_keys($this->elements)]];
    foreach ($sections as $key => $section) {
      $elems = [];
      foreach ($section['elements'] as $k) {
        if (!isset($this->elements[$k]) || !isset($elements[$this->elements[$k]['bind']])) { continue; }
        $elem = array_merge($elements[$this->elements[$k]['bind']], $this->elements[$k]);
        if (!isset($elem['type'])  || !is_string($elem['type'])) { $elem['type']  = $k; }
        if (!isset($elem['extra']) || !is_array($elem['extra'])) { $elem['extra'] = []; }
        $elem['type'] == 'icon' && $elem['type'] = 'icons';
        $elem['type'] == 'html' && $elem['type'] = 'editor';
        $method = 'get'.studly_case($elem['type']).'Element';
        $elem = method_exists($this, $method) ? $this->$method($elem) : $this->getDefaultElement($elem);
        if (!array_key_exists('template', $elem) || 
            !is_string($elem['template']) || 
            !View::exists($elem['template'])) {
          $elem['template'] = in_array($elem['type'], ['integer']) ? 'ui::form.text' : 'ui::form.'.$elem['type'];
        }
        $elems[$k] = $elem;
      }
      if (empty($elems)) { continue; }
      $section['elements'] = $elems;
      $section['is_active'] = empty($config['sections']);
      $config['sections'][$key] = $section;
    }
    return $config;
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
    $element['options'] = $children(isset($element['parent_id']) ? $element['parent_id'] : 0);
    array_unshift($element['options'], ['divider' => 1]);
    return $this->getSelectElement($element);
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
    $element['header_render'] = $header_render;
    $element['line_render'] = $line_render;
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
    array_key_exists('options', $element) || $element['options'] = ['否', '是'];
    $default = ''; $options = []; $first = true;
    foreach ($element['options'] as $key => $value) {
      $options[] = ['label' => $value, 'value' => $key];
      if ($first) { $default = $key; $first = false; }
    }
    if (!array_key_exists('default', $element)) {
      $element['default'] = $default;
    }
    $element['options'] = $options;
    
    $template = '';
    array_key_exists('template', $element) && is_string($element['template']) && View::exists($element['template']) && $template = $element['template'];
    if (count($options) > 2) {
      $template = $template ?: 'ui::form.radio';
    } elseif (count($options) == 2) {
      $element['off'] = $element['on'] = [];
      foreach ($options as $option) {
        if ($element['off']) {
          $element['on'] = $option;
        } else {
          $element['off'] = $option;
        }
      }
      $template = $template ?: 'ui::form.switch';
    } else {
      $element['off'] = ['label' => '关闭', 'value' => 0];
      foreach ($options as $option) {
        $element['on'] = $option;
      }
      $template = $template ?: 'ui::form.switch';
    }
    
    $element['template'] = $template;
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
   * 
   * @access public
   * @return \Illuminate\Http\Response
   */
  public function edit() {
    $data = method_exists($this, 'formRenderring') ? $this->formRenderring($this->configDF()) : $this->configDF();
    array_key_exists('modal_title', $data) || $data['modal_title'] = trans('ui.dataform.title.create', ['name' => $data['name']]);
    array_key_exists('modal_title_icon', $data) || $data['modal_title_icon'] = 'fa fa-cubes';
    $template = array_key_exists('template', $data) && View::exists($data['template']) ? $data['template'] : 'ui::form.layout';
    $data['data'] = [];
    $data['method'] = 'save';
    return view($template, $data);
  }
  
  /**
   * 保存数据
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function handle(Request $request) {
    $method = $request->has('method') ? $request->get('method') : 'save';
    if (!method_exists($this, $method)) {
      throw new RuntimeException(trans('ui::exception.form.handle_not_supported', ['handle' => $method]));
    }
    $handle = 'beforeData'.studly_case($method);
    $data = method_exists($this, $handle) ? $this->$handle($request) : $request->all();
    
    $config = method_exists($this, 'formRenderring') ? $this->formRenderring($this->configDF()) : $this->configDF();
    if ($this->$method($data)) {
      $handle = 'afterData'.studly_case($method);
      method_exists($this, $handle) && $this->$handle($request, $data);
      $message = property_exists($this, 'handle_'.$method.'_success') ? $this->{'handle_'.$method.'_success'} : trans('ui::form.handle.'.$method.'_success', ['name' => $config['name']]);
      $status = 1;
    } else {
      $message = property_exists($this, 'handle_'.$method.'_success') ? $this->{'handle_'.$method.'_failure'} : trans('ui::form.handle.'.$method.'_failure', ['name' => $config['name']]);
      $status = 0;
    }
    return response()->json(compact('status', 'message'));
  }
  
  /**
   * 保存数据
   * @access protected
   * @param \YitOS\MModelFactory\Eloquent\Model|array $data
   * @return bool
   */
  protected function save($data) {
    if ($data instanceof \YitOS\MModelFactory\Eloquent\Model) {
      $data->parents = $data->getParentsIds();
      $data->children = $data->getChildrenIds();
      $result = $data->save();
      
      $model = $data;
      while ($result && $parent = $model->rel_parent) {
        $parent->children = $parent->getChildrenIds();
        $parent->save();
        $model = $parent;
      }
      return $result;
    } elseif (isset($data['_id'])) {
      $builder = $this->definedBuilder();
      $_id = $data['_id'];
      unset($data['_id']);
      $builder->where('_id', $_id)->update($data);
      return $data;
    } else {
      $builder = $this->definedBuilder();
      $_id = $builder->insertGetId($data);
      $data['_id'] = $_id;
      return $data;
    }
  }
  
}