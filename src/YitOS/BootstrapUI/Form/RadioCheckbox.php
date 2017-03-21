<?php namespace YitOS\BootstrapUI\Form;

/**
 * 单（多）选（表单）前端助手
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 * @see \YitOS\BootstrapUI\Form\Element
 */
class RadioCheckbox extends Boolean {
  
  /**
   * 元素类型
   * @var string 
   */
  protected $type = 'radio';
  
  /**
   * 多选
   * @access public
   * @param bool $checkbox
   * @return \YitOS\Foundation\BootstrapUI\Form\RadioCheckbox
   */
  public function multi() {
    $this->type = 'checkbox';
    return $this;
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
    $value = array_key_exists($this->name, $data) ? $data[$this->name] : $default;
    $html = '<div class="mt-'.$this->type.'-inline">';
    $name = $this->name;
    if ($this->type == 'checkbox') {
      $name .= '[]';
    }
    foreach ($this->options as $option) {
      $checked = false;
      if ($this->type == 'radio') {
        $checked = $value == $option['value'];
      } else {
        $checked = is_array($value) && in_array($option['value'], $value);
      }
      
      $html .= '<label class="mt-'.$this->type.'">';
      $html .= '<input type="radio" name="'.$name.'" value="'.$option['value'].'"'.($checked?' checked':'').'> '.$option['label'].' <span></span>';
      $html .= '</label>';
    }
    $html .= '</div>'.$this->getHelperHtml();
    return $html;
  }
  
}
