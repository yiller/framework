<?php namespace YitOS\BootstrapUI\Form;

/**
 * Bootstrap UI元素基类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI\Form
 */
abstract class Element {
  
  /**
   * 元素唯一标识
   * @var string 
   */
  protected $id = '';
  
  /**
   * 元素名字
   * @var string 
   */
  protected $name = '';
  
  /**
   * 额外属性
   * @var array 
   */
  protected $extra = [];
  
  /**
   * 代位字符
   * @var string 
   */
  protected $placeholder = '';
  
  /**
   * 帮助信息
   * @var string 
   */
  protected $helper = '';
  
  /**
   * 创建实例
   * @access public
   * @return void
   */
  public function __construct() {
    $this->id = strtolower(str_random(10));
  }
  
  /**
   * 设置元素的name属性
   * @access public
   * @param string $name
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public function name($name) {
    $this->name = $name;
    return $this;
  }
  
  /**
   * 设置元素的额外属性
   * @access public
   * @param string $extra
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public function extra($extra) {
    $this->extra = $extra;
    return $this;
  }
  
  /**
   * 设置元素的代位字符
   * @access public
   * @param string $placeholder
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public function placeholder($placeholder) {
    $this->placeholder = $placeholder;
    return $this;
  }
  
  /**
   * 设置元素的帮助信息
   * @access public
   * @param string $helper
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public function helper($helper) {
    $this->helper = $helper;
    return $this;
  }
  
  /**
   * 获得元素的附加样式类
   * @access protected
   * @return string
   */
  protected function getStyle() {
    $style = isset($this->extra['class']) ? $this->extra['class'] : '';
    return $style;
  }
  
  /**
   * 获得元素的附加内联样式
   * @access protected
   * @return string
   */
  protected function getCss() {
    $css = isset($this->extra['css']) ? $this->extra['css'] : '';
    return $css;
  }
  
  /**
   * 获得元素的附加属性
   * @access protected
   * @return string
   */
  protected function getExtraAttributes() {
    $attributes = [];
    foreach ($this->extra as $key => $value) {
      if (in_array($key, ['css', 'class'])) {
        continue;
      }
      $attributes[] = $key.'="'.$value.'"';
    }
    return implode(' ', $attributes);
  }
  
  /**
   * 获得元素的帮助信息HTML
   * @access protected
   * @return string
   */
  protected function getHelperHtml() {
    $html = '';
    if ($this->helper) {
      $html = '<span class="help-block"> '.$this->helper.' </span>';
    }
    return $html;
  }
  
  /**
   * 获得元素实例
   * 
   * @static
   * @access public
   * @return \YitOS\Foundation\BootstrapUI\Form\Element
   */
  public static function load() {
    return new static();
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
  abstract public function render($data, $default = '');
  
}
