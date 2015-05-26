<?php namespace YitOS\Backend\Http\ViewComposers;

use Illuminate\Contracts\View\View;
use YitOS\Model\Page;

class TKDComposer {
  
  public function compose(View $view) {
    list($title, $keywords, $description) = Page::getTKD();
    
    if (app('auth')->check() && app('request')->user()->enterprise) {
      $title .= ($title ? '_' : '') . app('request')->user()->enterprise->name;
    }
    $title .= ($title ? '_' : '') . config('yitos.backend.name');
    
    $view->with('title', $title);
    if ($keywords) $view->with('keywords', $keywords);
    if ($description) $view->with('description', $description);
  }
  
}