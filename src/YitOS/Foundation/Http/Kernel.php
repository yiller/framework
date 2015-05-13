<?php namespace YitOS\Foundation\Http;

use Illuminate\Routing\Router;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as HttpKernel;


class Kernel extends HttpKernel {
  public function __construct(Application $app, Router $router) {
    parent::__construct($app, $router);
    
    $router->middleware('acl', 'YitOS\Auth\Middleware\AuthenticateWithACL');
  }
}
