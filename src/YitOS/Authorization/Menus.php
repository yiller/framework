<?php namespace YitOS\Authorization;

/**
 * 页面菜单
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Authorization
 */
class Menus {
  
  /**
   * 菜单类名
   * @var string 
   */
  protected $class = '';
  
  /**
   * 菜单项列表
   * @var array 
   */
  protected $menus = [];
  
  /**
   * 渲染页面菜单HTML
   * 
   * @access public
   * @return string
   */
  public function render() {
    $func = function($menu) use(&$func) {
      $children = $menu->getChildrenMenus();
      $style = $menu->style;
      if ($menu->is_virtual && $children->isEmpty()) {
        $classname = 'heading'.(array_key_exists('class', $style)?' '.$style['class']:'');
      } else {
        $classname = 'nav-item'.(array_key_exists('class', $style)?' '.$style['class']:'');
      }
      $inline = array_key_exists('inline', $style)?$style['inline']:'';
      $href = $menu->link;

      $html = '';
      $html .= '<li class="'.$classname.'"'.($inline?' style="'.$inline.'"':'').'>';
      if ($menu->is_virtual && $children->isEmpty()) {
        $html .= '<h3>';
        if ($menu->icon) {
          $html .= '<i class="'.$menu->icon.'"></i>';
        }
        $html .= $menu->label;
        $html .= '</h3>';
      } else {
        $html .= '<a href="'.$href.'" class="'.($children->isEmpty()?'':'nav-link nav-toggle').'">';
        if ($menu->icon) {
          $html .= '<i class="'.$menu->icon.'"></i>';
        }
        $html .= '<span class="title">'.$menu->label.'</span>';
        if (!$children->isEmpty()) {
          $html .= '<span class="arrow"></span>';
        }
        $html .= '</a>';
      }

      if (!$children->isEmpty()) {
        $html .= '<ul class="sub-menu">';
        foreach ($children as $child) { $html .= $func($child); }
        $html .= '</ul>';
      }
      $html .= '</li>';
      return $html;
    };
    
    return $this->walk($func);
  }
  
  protected function walk($func) {
    $html = '';
    foreach ($this->menus as $menu) {
      $html .= $func($menu);
    }
    return $html;
  }
  
  /**
   * 设置菜单项列表
   * @access public
   * @param \Illuminate\Database\Eloquent\Collection|static[] $menus
   * @return \YitOS\Authorization\Menus
   */
  public function menus($menus) {
    $this->menus = $menus;
    return $this;
  }
  
  /**
   * 设置菜单类名
   * @access public
   * @param string $classname
   * @return \YitOS\Authorization\Menus
   */
  public function classname($class) {
    $this->class = $class;
    return $this;
  }
  
  /**
   * 获得渲染的菜单组
   * 
   * @static
   * @access public
   * @param string $class
   * @return \YitOS\Authorization\Menus
   */
  public static function load($class) {
    $instance = new static();
    $menu = M($class);
    if ($menu instanceof \YitOS\Contracts\Authorization\Menu) {
      $instance->classname(get_class($menu))->menus($menu::getRootMenus());
    }
    return $instance;
  }
  
}
