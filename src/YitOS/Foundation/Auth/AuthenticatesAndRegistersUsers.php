<?php namespace YitOS\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;

trait AuthenticatesAndRegistersUsers {
  
  protected $auth;
  protected $registrar;
  protected $loginRules = [
    'account' => [
      ['name' => 'required', 'message' => '请输入用户名、电子邮箱或手机号码！'],
      ['name' => 'account', 'message' => '请输入用户名、电子邮箱或手机号码2！']
    ],
    'password' => [
      ['name' => 'required', 'message' => '请输入密码！']
    ],
  ];
  
  public function getRegister() {
    return view('auth.register');
  }
  
  public function postRegister(Request $request) {
    $validator = $this->registrar->validator($request->all());
    if ($validator->fails()) {
			$this->throwValidationException($request, $validator);
		}
    $this->auth->login($this->registrar->create($request->all()));
    return redirect($this->redirectPath());
  }
  
  public function anyLogin(Request $request) {
    if ($request->method() === 'POST') {
      $this->validate($request, rules_convert($this->loginRules)->get('rules'), rules_convert($this->loginRules)->get('messages'));
      $credentials = ['active' => 1, 'password' => $request->get('password')];
      $factory = $this->getValidationFactory(); $success = false;
      if (($v = $factory->make($request->all(), ['account' => 'email'])) && !($v->fails())) {
        $success = $this->auth->attempt(array_merge($credentials, ['email' => $request->get('account')]), $request->has('remember'));
      } elseif (($v = $factory->make($request->all(), ['account' => 'phoneCN'])) && !($v->fails())) {
        $success = $this->auth->attempt(array_merge($credentials, ['phone' => $request->get('account')]), $request->has('remember')) || 
                   $this->auth->attempt(array_merge($credentials, ['username' => $request->get('account')]), $request->has('remember'));
      } else {
        $success = $this->auth->attempt(array_merge($credentials, ['username' => $request->get('account')]), $request->has('remember'));
      }
    
      if ($success) {
        return redirect()->intended($this->redirectPath());
      }
    
      return redirect($this->loginPath())
              ->withInput($request->only('account', 'remember'))
              ->withErrors([
                'account' => $this->getFailedLoginMessage(),
              ]);
    }
    return view($this->loginView(), [
      'config' => rules_convert($this->loginRules, 'jquery')
    ]);
  }
  
  protected function getFailedLoginMessage() {
		return '用户名或密码错误！';
	}
  
  public function anyLogout() {
		$this->auth->logout();
		return redirect(property_exists($this, 'afterLogoutRoute') ? route($this->afterLogoutRoute) : '/');
	}
  
  public function redirectPath() {
		if (property_exists($this, 'afterLoginRoute')) {
			return route($this->afterLoginRoute);
		}
		return property_exists($this, 'redirectTo') ? $this->redirectTo : route(config('auth.afterLoginRoute'));
	}
  
  public function loginPath()	{
		return route(property_exists($this, 'loginRoute') ? $this->loginRoute : config('auth.loginRoute'));
	}
  
  protected function allowRegister() {
    return (!property_exists($this, 'allowRegister') || $this->allowRegister);
  }
  
  protected function loginView() {
    return property_exists($this, 'loginView') ? $this->loginView : config('auth.loginView');
  }
  
}
