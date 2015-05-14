<?php namespace YitOS\Auth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\Middleware;

class AuthenticateWithACL implements Middleware {
  
  protected $auth;
  
  public function __construct(Guard $auth) {
    $this->auth = $auth;
  }
  
  protected function allowAccess($action, $user = null) {
    return true;
  }
  
  public function handle($request, Closure $next) {
    if ($this->auth->guest()) { // 用户未登陆
      if ($request->ajax()) {
        return response('Unauthorized.', 401);
      } elseif (array_key_exists('namespace', $request->route()->getAction()) && 
                ($request->route()->getAction()['namespace'] === 'YitOS\Backend\Http\Controllers') && 
                ($except = ['YitOS\Backend\Http\Controllers\IndexController@anyLogin',
                            'YitOS\Backend\Http\Controllers\IndexController@anyLogout']) && 
                array_search($request->route()->getActionName(), $except) === false) {
        return redirect()->guest(route('backend.login'));
      } elseif ((!array_key_exists('namespace', $request->route()->getAction()) || 
                ($request->route()->getAction()['namespace'] !== 'YitOS\Backend\Http\Controllers')) && 
                (!$request->route()->getName() || array_search($request->route()->getName(), [
                    config('auth.loginRoute'),
                    config('auth.logoutRoute')
                ]) === false)) {
        return redirect()->guest(route(config('auth.loginRoute')));
      }
      //dd($request->route()->getAction());
      //return($request->ajax() ? response('Unauthorized.', 401) : redirect()->guest('auth/login'));
    } else { // 用户已登录
      
    }
    return $next($request);
  }
  
}
