<?php namespace YitOS\_G\Http;

use Illuminate\Routing\Router;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Kernel as BaseKernel;


class Kernel extends BaseKernel {
  
  public function __construct(Application $app, Router $router) {
    parent::__construct($app, $router);
    
    // 未登录用户可访问
    $router->middleware('guest', 'YitOS\_G\Http\Middleware\RedirectIfAuthenticated');
    // ACL控制
    $router->middleware('acl', 'YitOS\_G\Http\Middleware\AuthenticateWithACL');
    // 当前企业成员可访问
    $router->middleware('company', 'YitOS\_G\Http\Middleware\AuthenticateWithCompany');
  }
  
}
