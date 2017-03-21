<?php namespace YitOS\BootstrapUI\Form;

/**
 * 布尔值（表单）前端助手
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 * @see \YitOS\BootstrapUI\Form\Element
 */
class Boolean extends Element {
  
  /**
   * 备选项目列表
   * @var array 
   */
  protected $options = [];
  
  /**
   * 获得元素实例
   * @static
   * @access public
   * @param string $name
   * @param array $extra
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public static function load($name, $options = [], $extra = []) {
    return new static($name, $options, $extra);
  }
  
  /**
   * 创建实例
   * @access public
   * @param string $name
   * @param array $options
   * @param array $extra
   * @return void
   */
  public function __construct($name = '', $options = [], $extra = []) {
    $this->id = strtolower(str_random(10));
    $this->name = $name;
    $this->extra = $extra;
    $this->options = $options;
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
    $on = $off = [];
    foreach ($this->options as $option) {
      $var = $option['value'] ? 'on' : 'off';
      $$var = $option;
    }
    $value = array_key_exists($this->name, $data) ? $data[$this->name] : $default;
    $html = '<input type="checkbox" '
            . 'name="'.$this->name.'" '
            . 'class="make-switch" '
            . $this->getExtraAttributes()
            . 'data-on-text="'.$on['label'].'" '
            . 'data-off-text="'.$off['label'].'" '
            . 'value="'.$on['value'].'"'
            . ($value == $on['value'] ? ' checked' : '')
            . '>';
    $html .= $this->getHelperHtml();
    return $html;
  }
  
}
