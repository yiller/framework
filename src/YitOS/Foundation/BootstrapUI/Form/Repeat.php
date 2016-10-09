<?php namespace YitOS\Foundation\BootstrapUI\Form;

/**
 * 重复元素（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\BootstrapUI\Form
 * @see \YitOS\Foundation\BootstrapUI\Form\Element
 */
class Repeat extends Element {
  
  /**
   * 头部渲染
   * @var mixed 
   */
  protected $header_render = null;
  
  /**
   * 单元素渲染
   * @var mixed 
   */
  protected $line_render = null;
  
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
    if (!is_array($values)) {
      $values = [];
    }
    
    $html = '<table class="table table-striped table-bordered table-hover">';
    $html .= '<thead><tr role="row" class="heading"><th width="2%"></th>';
    if ($this->header_render) {
      $func = $this->header_render;
      $html .= $func();
    }
    $html .= '<th width="10%"></th></tr></thead>';
    $html .= '<tbody data-repeater-list="'.$this->name.'">';
    $func = $this->line_render;
    if ($values) {
      foreach ($values as $key => $value) {
        $html .= '<tr data-repeater-item><td>#'.($key+1).'</td>';
        $html .= $func($value);
        $html .= '<td><a href="javascript:;" data-repeater-delete class="btn btn-danger mt-repeater-delete" data-toggle="confirmation" data-original-title="'.trans('ui::form.repeat.text_delete_confirmation').'" data-btn-ok-label="'.trans('ui::form.repeat.button_confirmation_confirm').'" data-btn-cancel-label="'.trans('ui::form.repeat.button_confirmation_cancel').'"><i class="fa fa-close"></i> '.trans('ui::form.repeat.button_delete').' </a></td></tr>';
      }
    } else {
      $html .= '<tr data-repeater-item><td>#1</td>';
      $html .= $func([]);
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
    $this->header_render = $func;
    return $this;
  }
  
  /**
   * 单元素渲染函数
   * @access public
   * @param mixed $func
   * @return \YitOS\Foundation\BootstrapUI\Form\Repeat
   */
  public function item($func) {
    $this->line_render = $func;
    return $this;
  }
  
}
