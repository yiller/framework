<?php namespace YitOS\Backend\Providers;

use Illuminate\Routing\Router;

trait RouteServiceProvider {
  
  public function backend(Router $router) {
    $router->group(['namespace' => 'YitOS\Backend\Http\Controllers'], function($router) {

      $router->group(['domain' => config('yitos.backend.prefix').'.'.config('app.domain')], function($router) {
        $router->controller('/', 'IndexController', [
          'getIndex' => 'yitos.backend.redirect',
          'anyLogin' => 'yitos.backend.login',
          'anyLogout' => 'yitos.backend.logout',
        ]);
      });
      
      $router->group(['domain' => '{company}.'.config('app.domain')], function($router) {
        $router->get('/', ['as' => 'yitos.backend.dashboard', 'uses' => 'DashboardController@index']);
        $router->get('users', 'UserController@index');
      });
      
    });
  }
  
}