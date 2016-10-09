<?php namespace YitOS\Foundation\BootstrapUI\Form;

/**
 * 多行文本框（表单）前端助手
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\BootstrapUI\Form
 * @see \YitOS\Foundation\BootstrapUI\Form\Element
 * @see \YitOS\Foundation\BootstrapUI\Form\Text
 */
class Tags extends Text {
  
  /**
   * 获得元素的附加属性
   * @access protected
   * @return string
   */
  protected function getExtraAttributes() {
    $this->extra['data-role'] = 'tagsinput';
    return parent::getExtraAttributes();
  }
  
}
