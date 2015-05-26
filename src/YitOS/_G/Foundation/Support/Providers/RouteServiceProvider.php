<?php namespace YitOS\_G\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseServiceProvider;

class RouteServiceProvider extends BaseServiceProvider {
  
  protected function loadRoutes() {
    $this->app->call([$this, 'backend']);
    parent::loadRoutes();
  }
  
  public function backend(Router $router) {
    $router->group(['namespace' => 'YitOS\Backend\Http\Controllers'], function($router) {
      
      $router->group(['domain' => config('yitos.backend.prefix').'.'.config('app.domain')], function($router) {
        $router->controller('/', 'IndexController', [
          'getIndex' => 'yitos.backend.redirect',
          'anyLogin' => 'yitos.backend.login',
        ]);
      });
      
      $router->group(['domain' => '{company}.'.config('app.domain')], function($router) {
        $router->get('/', ['as' => 'yitos.backend.dashboard', 'uses' => 'DashboardController@index']);
        $router->get('users', 'UserController@index');
      });
      
    });
  }
  
}