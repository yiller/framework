<?php namespace YitOS\Foundation\BootstrapUI\Form;

/**
 * 所见即所得编辑器（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\BootstrapUI\Form
 * @see \YitOS\Foundation\BootstrapUI\Form\Element
 */
class Editor extends Element {
  
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
    $value = array_key_exists($this->name, $data) ? $data[$this->name] : $default;
    $html = '<textarea name="'.$this->name.'" '
            . 'class="summernote form-control '.$this->getStyle().'" '
            . $this->getExtraAttributes()
            . 'rows="6" '
            . 'data-error-container="#editor_'.$this->id.'_error">';
    $html .= $value;
    $html .= '</textarea>';
    $html .= '<div id="editor_'.$this->id.'_error">'.$this->helper.'</div>';
    return $html;
  }
  
}
