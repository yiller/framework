<?php namespace YitOS\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider {
  
  protected function loadRoutes() {
    $this->app->call([$this, 'backend']);
    parent::loadRoutes();
  }
  
  public function backend(Router $router) {
    $router->group(['namespace' => 'YitOS\Backend\Http\Controllers', 'prefix' => config('backend.prefix')], function($router) {
      Route::get('/', function(){
        return 'Backend';
      });
    });
  }
  
}