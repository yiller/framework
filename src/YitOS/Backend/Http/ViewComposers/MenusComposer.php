<?php namespace YitOS\Backend\Http\ViewComposers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class MenusComposer {
  
  protected $request;
  
  public function __construct(Request $request) {
    $this->request = $request;
  }
  
  public function compose(View $view) {
    /*$menus = collect();
    $current = $this->getCurrentMenu();
    
    foreach (Menu::getMenus() as $menu) {
      $key = $menu->id; $item = $menu;
      $menu->actived = ($menu == $current) ? 1 : 0;
      if ($menu->parent_id > 0 && $menus->has($menu->parent_id)) {
        $item = $menus->get($menu->parent_id);
        if (!isset($item->children)) $item->children = collect();
        $item->children->put($menu->id, $menu);
        if ($menu->actived > 0) $item->actived = 1;
      }
      $menus->put($item->id, $item);
    }

    $view->with('menus', $menus);*/
    $view->with('menus', [
        '123'
    ]);
  }
  
  public function getCurrentMenu() {
    /*$menu = Menu::getMenus()->where('action',$this->request->route()->getActionName())->filter(function($item) {
      $matches = true;
      if ($item->action_args) foreach ($item->action_args as $k => $v) if ($this->request->route($k) != $v) { $matches = false; break; }
      return $matches;
    });
    return $menu->isEmpty() ? null : $menu->first();*/
  }
  
}