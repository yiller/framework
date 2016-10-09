<?php namespace YitOS\Foundation\BootstrapUI\Form;

/**
 * 多行文本框（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\BootstrapUI\Form
 * @see \YitOS\Foundation\BootstrapUI\Form\Element
 */
class Textarea extends Element {
  
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
            . 'class="form-control '.$this->getStyle().'" '
            . 'style="'.$this->getCss().'"'
            . 'placeholder="'.$this->placeholder.'"'
            . $this->getExtraAttributes()
            . 'rows="6"'
            . '>';
    $html .= $value;
    $html .= '</textarea>';
    $html .= $this->getHelperHtml();
    return $html;
  }
  
}
