<?php namespace YitOS\_G\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class AuthenticateWithCompany {

	protected $auth;

	public function __construct(Guard $auth) {
		$this->auth = $auth;
	}
  
	public function handle($request, Closure $next) {
    $user = $this->auth->user();
    if (!$user->enterprise || $user->enterprise->slug != $request->route('company')) {
      return $request->ajax() ? response('Page NotFound.', 404) : abort(404);
    }
		return $next($request);
	}

}
