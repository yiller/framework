<?php namespace YitOS\BootstrapUI\Form;

/**
 * 下拉菜单（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 * @see \YitOS\BootstrapUI\Form\Element
 */
class Select extends Boolean {
  
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
    $html = '<select name="'.$this->name.'" '
            //. 'class="bs-select form-control '.$this->getStyle().'" '
            . 'class="select2me form-control '.$this->getStyle().'" '
            . 'style="'.$this->getCss().'" '
            . 'data-show-subtext="true" '
            . $this->getExtraAttributes()
            . '>';
    $render = function($option, $value) use(&$render) {
      $plain = '';
      if (array_key_exists('children', $option)) {
        $plain .= '<optgroup label="'.$option['label'].'">';
        foreach ($option['children'] as $child) {
          $plain .= $render($child, $value);
        }
        $plain .= '</optgroup>';
      } else {
        $extra = (isset($option['value']) && $value == $option['value']) ? ' selected' : '';
        if (array_key_exists('content', $option)) {
          $plain .= '<option value="'.$option['value'].'" data-content="'.$option['content'].'"'.$extra.'>'.$option['label'].'</option>';
        } elseif (array_key_exists('icon', $option)) {
          $plain .= '<option value="'.$option['value'].'" data-icon="'.$option['icon'].'"'.$extra.'>'.$option['label'].'</option>';
        } elseif (array_key_exists('subtext', $option)) {
          $plain .= '<option value="'.$option['value'].'" data-subtext="'.$option['subtext'].'"'.$extra.'>'.$option['label'].'</option>';
        } elseif (array_key_exists('divider', $option)) {
          $plain .= '<option data-divider="true"></option>';
        } else {
          $plain .= '<option value="'.$option['value'].'"'.$extra.'>'.$option['label'].'</option>';
        }
      }
      return $plain;
    };
    
    foreach ($this->options as $option) {
      $html .= $render($option, $value);
    }
    
    $html .= '</select>'.$this->getHelperHtml();
    return $html;
  }
  
}
