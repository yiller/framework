<?php namespace YitOS\_G\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Http\RedirectResponse;

class AuthenticateWithACL implements Middleware {
  
  protected $auth;
  
  public function __construct(Guard $auth) {
    $this->auth = $auth;
  }
  
  /**
   * 当前登录用户是否有权限访问当前页面
   * 
   * @param Request $request
   * @return boolean
   */
  protected function permission($request) {
    return true;
  }
  
  public function handle($request, Closure $next) {
    if ($this->auth->guest()) { // 用户未登陆
      if (!$request->route()->getName() || $request->route()->getName() != config('auth.routes.login')) // 访问授权页面 跳转到登录页
        return $request->ajax() ? response('Unauthorized.', 401) : redirect()->guest(route(config('auth.routes.login')));
    } else { // 用户已登录
      if ($request->route()->getName() && $request->route()->getName() == config('auth.routes.login')) // 访问登录页面 跳转到登录之后
        return new RedirectResponse(route(config('auth.routes.after_login')));
      if ($this->permission($request) === false) // 用户权限不足 报告403异常
        abort(403);
    }
    /*if ($this->auth->guest()) { // 用户未登陆
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
      
    }*/
    return $next($request);
  }
  
}
