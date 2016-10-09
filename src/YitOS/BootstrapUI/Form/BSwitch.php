<?php namespace YitOS\BootstrapUI\Form;

/**
 * 下拉菜单（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 * @see \YitOS\BootstrapUI\Form\Element
 */
class BSwitch extends Element {
  
  /**
   * 启用配置
   * @var array 
   */
  protected $on = [];
  
  /**
   * 关闭配置
   * @var array 
   */
  protected $off = [];
  
  /**
   * 设置备选项
   * @access public
   * @param array $off
   * @param array $on
   * @return \YitOS\Foundation\BootstrapUI\Form\BSwitch
   */
  public function options($off, $on) {
    $this->off = $off;
    $this->on = $on;
    return $this;
  }
  
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
    $html = '<input type="checkbox" '
            . 'name="'.$this->name.'" '
            . 'class="make-switch" '
            . $this->getExtraAttributes()
            . 'data-on-text="'.$this->on['label'].'" '
            . 'data-off-text="'.$this->off['label'].'" '
            . 'value="'.$this->on['value'].'"'
            . ($value == $this->on['value'] ? ' checked' : '')
            . '>';
    $html .= $this->getHelperHtml();
    return $html;
  }
  
}
