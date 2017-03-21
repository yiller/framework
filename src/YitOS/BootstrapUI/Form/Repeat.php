<?php namespace YitOS\BootstrapUI\Form;

/**
 * 重复元素（表单）前端助手
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 * @see \YitOS\BootstrapUI\Form\Element
 */
class Repeat extends Element {
  
  /**
   * 头部渲染
   * @var mixed 
   */
  protected $header = null;
  
  /**
   * 单元素渲染
   * @var mixed 
   */
  protected $line = null;
  
  /**
   * 行元素定义
   * @var array 
   */
  protected $items = [];
  
  /**
   * 创建实例
   * @access public
   * @param string $name
   * @param array $items
   * @return void
   */
  public function __construct($name = '', $items = []) {
    parent::__construct();
    $this->name = $name;
    $this->items = $items;
    $this->header = function() {
      if (!$this->items) return '';
      $width = floor(75 / count($this->items));
      $html = '';
      foreach ($this->items as $item) $html .= '<th width="'.$width.'%">'.$item['label'].'</th>';
      return $html;
    };
    $this->line = function($data = []) {
      $html = '';
      foreach ($this->items as $item) {
        $html .= '<td>';
        $instance = null; $default = '';
        if (in_array($item['type'], ['string', 'slug'])) {
          $instance = Text::load($item['name'], $item['extra'])
                  ->placeholder($item['placeholder']);
        } elseif ($item['type'] == 'select') {
          $instance = Select::load($item['name'], $item['options'], $item['extra']);
        } elseif ($item['type'] == 'boolean') {
          $instance = Boolean::load($item['name'], $item['options'], $item['extra']);
        }
        $instance && $html .= $instance->helper(isset($item['helper']) ? $item['helper'] : '')->render($data, isset($default)?$default:'');
        $html .= '</td>';
      }
      return $html;
    };
  }
  
  /**
   * 获得元素实例
   * @static
   * @access public
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public static function load($name, $items = []) {
    return new static($name, $items);
  }
  
  /**
   * 渲染元素HTML
   * 
   * @abstract
   * @access public
   * @param array $data
   * @param mixed $default
   * @return string
   */
  public function render($data, $default = '') {
    $values = array_key_exists($this->name, $data) ? $data[$this->name] : $default;
    is_array($values) || $values = [];
    
    $html = '<table class="table table-striped table-bordered table-hover">';
    $html .= '<thead><tr role="row" class="heading"><th width="2%"></th>';
    $html .= call_user_func($this->header);
    $html .= '<th width="10%"></th></tr></thead>';
    $html .= '<tbody data-repeater-list="'.$this->name.'">';
    if ($values) {
      foreach ($values as $key => $value) {
        $html .= '<tr data-repeater-item><td>#'.($key+1).'</td>';
        $html .= call_user_func($this->line, $value);
        $html .= '<td><a href="javascript:;" data-repeater-delete class="btn btn-danger mt-repeater-delete" data-toggle="confirmation" data-original-title="'.trans('ui::form.repeat.text_delete_confirmation').'" data-btn-ok-label="'.trans('ui::form.repeat.button_confirmation_confirm').'" data-btn-cancel-label="'.trans('ui::form.repeat.button_confirmation_cancel').'"><i class="fa fa-close"></i> '.trans('ui::form.repeat.button_delete').' </a></td></tr>';
      }
    } else {
      $html .= '<tr data-repeater-item><td>#1</td>';
      $html .= call_user_func($this->line);
      $html .= '<td><a href="javascript:;" data-repeater-delete class="btn btn-danger mt-repeater-delete" data-toggle="confirmation" data-original-title="'.trans('ui::form.repeat.text_delete_confirmation').'" data-btn-ok-label="'.trans('ui::form.repeat.button_confirmation_confirm').'" data-btn-cancel-label="'.trans('ui::form.repeat.button_confirmation_cancel').'"><i class="fa fa-close"></i> '.trans('ui::form.repeat.button_delete').' </a></td></tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<a href="javascript:;" data-repeater-create class="btn btn-info mt-repeater-add"><i class="fa fa-plus"></i> '.trans('ui::form.repeat.button_add').' </a>';
    return $html;
  }
  
  /**
   * 头部渲染函数
   * @access public
   * @param mixed $func
   * @return \YitOS\Foundation\BootstrapUI\Form\Repeat
   */
  public function header($func) {
    $func && $this->header = $func;
    return $this;
  }
  
  /**
   * 单元素渲染函数
   * @access public
   * @param mixed $func
   * @return \YitOS\Foundation\BootstrapUI\Form\Repeat
   */
  public function line($func) {
    $func && $this->line = $func;
    return $this;
  }
  
  /**
   * 行元素定义
   * @access public
   * @param array $items
   * @return \YitOS\Foundation\BootstrapUI\Form\Repeat
   */
  public function items($items) {
    $this->items = $items;
    return $this;
  }
  
}
