<?php namespace YitOS\Backend\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use YitOS\Model\Menu;

class MenusComposer {
  
  public function compose(View $view) {
    $view->with('menus', Menu::getMenusTree());
  }
  
}