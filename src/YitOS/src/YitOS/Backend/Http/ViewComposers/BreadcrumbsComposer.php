<?php namespace YitOS\Backend\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use YitOS\Model\Menu;

class BreadcrumbsComposer {
  
  public function compose(View $view) {
    $current = Menu::getMenu();
    $view->with('breadcrumbs', $current ? $current->parents() : collect());
  }
  
}