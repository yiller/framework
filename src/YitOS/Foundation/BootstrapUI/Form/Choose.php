<?php namespace YitOS\Foundation\BootstrapUI\Form;

/**
 * 单（多）选（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\BootstrapUI\Form
 * @see \YitOS\Foundation\BootstrapUI\Form\Element
 */
class Choose extends Element {
  
  /**
   * 元素类型
   * @var string 
   */
  protected $type = 'radio';
  
  /**
   * 备选项目列表
   * @var array 
   */
  protected $options = [];
  
  /**
   * 设置备选项
   * @access public
   * @param array $options
   * @return \YitOS\Foundation\BootstrapUI\Form\Choose
   */
  public function options($options) {
    $this->options = $options;
    return $this;
  }
  
  /**
   * 多选
   * @access public
   * @param bool $checkbox
   * @return \YitOS\Foundation\BootstrapUI\Form\Choose
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
