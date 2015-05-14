<?php namespace YitOS\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\Middleware;

class AuthenticateWithACL implements Middleware {
  
  protected $auth;
  
  public function __construct(Guard $auth) {
    $this->auth = $auth;
  }
  
  public function handle($request, Closure $next) {
    if ($this->auth->guest()) { // 用户未登陆
      if ($request->ajax()) {
        return response('Unauthorized.', 401);
      } elseif (array_key_exists('namespace', $request->route()->getAction()) && 
                ($request->route()->getAction()['namespace'] === 'YitOS\Backend\Http\Controllers') && 
                ($except = ['YitOS\Backend\Http\Controllers\IndexController@getLogin',
                            'YitOS\Backend\Http\Controllers\IndexController@postLogin',
                            'YitOS\Backend\Http\Controllers\IndexController@getLogout']) && 
                array_search($request->route()->getActionName(), $except) === false) {
        return redirect()->guest(route('backend.login'));
      } elseif (!array_key_exists('namespace', $request->route()->getAction()) || 
                ($request->route()->getAction()['namespace'] !== 'YitOS\Backend\Http\Controllers')) {
        
        return redirect()->guest(route(config('auth.loginRoute')));
      }
      //dd($request->route()->getAction());
      //return($request->ajax() ? response('Unauthorized.', 401) : redirect()->guest('auth/login'));
    } else { // 用户已登录
      
    }
    return $next($request);
  }
  
}
