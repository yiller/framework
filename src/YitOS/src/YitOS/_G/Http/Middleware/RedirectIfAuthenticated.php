<?php namespace YitOS\_G\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;

class RedirectIfAuthenticated {

	protected $auth;

	public function __construct(Guard $auth) {
		$this->auth = $auth;
	}
  
	public function handle($request, Closure $next) {
		if ($this->auth->check()) { // 如果用户已登录
      return new RedirectResponse(route(config('auth.routes.after_login')));
		}
		return $next($request);
	}

}
