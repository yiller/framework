<?php namespace YitOS\_G\Model;

use Cache;
use YitOS\_G\Database\Eloquent\Model;

class Menu extends Model {
  
  protected $fillable = ['id'];
  
  public static function getMenus($group = 0) {
    $name = 'Menus\\'.$group;
    if (false === ($menus = Cache::get($name, false))) {
      $rows = self::where('group', $group)->orderBy('parent_id')->orderBy('sort_order','desc')->orderBy('id')->get();
      $menus = collect();
      foreach ($rows as $row) $menus->put($row->id, $row);
      $cached = !$menus->isEmpty();
      if ($group == 0) { // 后台菜单
        $menus->put(0, new static([
          'id' => 0,
          'label' => '控制台首页',
          'action' => 'YitOS\Backend\Http\Controllers\DashboardController@index',
          'sort_order' => 10000,
          'icon' => 'icon-home',
          'parent_id' => 0,
        ]));
        $menus->put(10000, new static([
          'id' => 10000,
          'label' => '系统工具',
          'style' => 1,
          'sort_order' => 100,
          'parent_id' => 0,
        ]));
        $menus->put(11000, new static([
          'id' => 11000,
          'label' => '用户管理',
          'action' => 'YitOS\Backend\Http\Controllers\UserController@index',
          'sort_order' => 90,
          'icon' => 'icon-user',
          'parent_id' => 0,
        ]));
        $menus->put(12000, new static([
          'id' => 12000,
          'label' => '权限管理',
          'action' => 'YitOS\Backend\Http\Controllers\DashboardController@index',
          'sort_order' => 80,
          'icon' => 'icon-users',
          'parent_id' => 0,
        ]));
        $menus->put(12001, new static([
          'id' => 12001,
          'label' => '用户角色',
          'action' => 'YitOS\Backend\Http\Controllers\DashboardController@index',
          'parent_id' => 12000,
          'sort_order' => 0,
        ]));
        $menus->put(12002, new static([
          'id' => 12002,
          'label' => '权限项管理',
          'action' => 'YitOS\Backend\Http\Controllers\DashboardController@index',
          'parent_id' => 12000,
          'sort_order' => 0,
        ]));
        $menus = $menus->sort(function($a, $b) {
          if ($a->parent_id > $b->parent_id) return 1;
          if ($a->parent_id < $b->parent_id) return -1;
          if ($a->sort_order > $b->sort_order) return -1;
          if ($a->sort_order < $b->sort_order) return 1;
          if ($a->id > $b->id) return 1;
          if ($a->id < $b->id) return -1;
          return 0;
        });
      }
      if ($cached) {
        Cache::put($name, $menus, 1);
      }
    }
    return $menus;
  }
  
  public static function getMenusTree($request = null, $group = 0) {
    $tree = collect();
    $current = static::getMenu($request);
    foreach (static::getMenus($group) as $menu) {
      $key = $menu->id; $item = $menu;
      $menu->actived = intval($menu == $current);
      if ($menu->parent_id > 0 && $tree->has($menu->parent_id)) {
        $item = $tree->get($menu->parent_id);
        if (!isset($item->children)) $item->children = collect();
        $item->children->put($menu->id, $menu);
        if ($menu->actived > 0) $item->actived = 1;
      }
      $tree->put($item->id, $item);
    }
    return $tree;
  }
  
  public static function getMenu($request = null) {
    $request = $request ?: app('request');
    $action = $request->route()->getActionName();
    $menu = static::getMenus()->where('action',$action)->filter(function($item) {
      $matches = true;
      if ($item->args) foreach ($item->args as $k => $v) if ($request->route($k) != $v) { $matches = false; break; }
      return $matches;
    });
    return $menu->isEmpty() ? null : $menu->first();
  }
  
  public function parents() {
    $menus = $this->getMenus($this->group);
    $item = $this;
    $parents = collect([$item]);
    while ($item->parent_id > 0) {
      if (!$menus->has($item->parent_id)) break;
      $item = $menus->get($item->parent_id);
      $parents->prepend($item);
    }
    return $parents;
  }
  
  public function getArgsAttribute() {
    return $this->action_args ? (array)(json_decode($this->action_args)) : [];
  }
  
  protected function getLinkAttribute() {
    if (!$this->action) {
      return 'javascript:;';
    } elseif ($this->args && $this->group == 0) {
      return YitOS_action('\\'.$this->action, $this->args);
    } elseif ($this->args) {
      return action('\\'.$this->action, $this->args);
    } elseif ($this->group == 0) {
      return YitOS_action('\\'.$this->action);
    } else {
      return action('\\'.$this->action);
    }
  }

}
