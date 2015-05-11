<?php namespace YitOS\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider {

	protected function loadRoutes() {
		$this->app->call([$this, 'backend']);
    parent::loadRoutes();
	}
  
  public function backend(Router $router) {
    $router->group(['prefix' => 'cpanel'], function($router)
		{
			Route::get('/', function(){
        return 'cpanel';
      });
		});
  }

}
