<?php namespace YitOS\Contracts\Authorization;

/**
 * 菜单项类接口
 * 
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Contracts\Authorization
 */
interface Menu {
  
  /**
   * 获得根菜单列表
   * 
   * @static
   * @access public
   * @return \Illuminate\Database\Eloquent\Collection|static[]
   */
  public static function getRootMenus();
  
  /**
   * 获得下级菜单
   * 
   * @access public
   * @return \Illuminate\Database\Eloquent\Collection|static[]
   */
  public function getChildrenMenus();
  
}
