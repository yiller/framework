<?php namespace YitOS\BootstrapUI\Form;

/**
 * 输入框（表单）前端助手
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 * @see \YitOS\BootstrapUI\Form\Element
 */
class Text extends Element {
  
  /**
   * 渲染元素HTML
   * @abstract
   * @access public
   * @param array $data
   * @param mixed $default
   * @return string
   */
  public function render($data, $default = '') {
    $value = array_key_exists($this->name, $data) ? $data[$this->name] : $default;
    $html = '<input type="text" '
            . 'name="'.$this->name.'"'
            . 'class="form-control '.$this->getStyle().'"'
            . 'style="'.$this->getCss().'"'
            . 'placeholder="'.$this->placeholder.'"'
            . $this->getExtraAttributes()
            . 'value="'.$value.'"'
            . '>';
    return $html.$this->getHelperHtml();
  }
  
}
