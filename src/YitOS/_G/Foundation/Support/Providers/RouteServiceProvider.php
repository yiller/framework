<?php namespace YitOS\_G\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseServiceProvider;

class RouteServiceProvider extends BaseServiceProvider {
  
  protected function loadRoutes() {
    
    $modules = config('yitos.modules', []);
    foreach ($modules as $name => $enabled) {
      if ($enabled) $this->app->call([$this, $name]);
    }
    
    parent::loadRoutes();
  }
  
}