<?php namespace YitOS\_G\Model;

use Cache;
use YitOS\_G\Database\Eloquent\Model;

class Page extends Model {
  
  public static function getTKD($action = '') {
    $action = $action ?: app('request')->route()->getActionName();
    $name = 'TKD\\'.$action;
    if (false === ($tkd = Cache::get($name, false))) {
      $page = Page::where('action',$action)->first();
      $tkd = ['', '', ''];
      $page && ($tkd = [$page->title, $page->keywords, $page->description]) && Cache::forever($name, $tkd);
    }
    return $tkd;
  }
  
}
