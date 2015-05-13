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
  
  public function getLogin() {
    return view($this->loginView(), [
      'config' => rules_convert($this->loginRules, 'jquery')
    ]);
  }
  
  public function postLogin(Request $request) {
    $this->validate($request, rules_convert($this->loginRules)->get('rules'), rules_convert($this->loginRules)->get('messages'));
    $credentials = $request->only('account', 'password');
    if ($this->auth->attempt($credentials, $request->has('remember'))) {
			return redirect()->intended($this->redirectPath());
		}
    
    return redirect($this->loginPath())
            ->withInput($request->only('username', 'remember'))
            ->withErrors([
              'email' => $this->getFailedLoginMessage(),
            ]);
  }
  
  protected function getFailedLoginMessage() {
		return '用户名或密码错误！';
	}
  
  public function getLogout() {
		$this->auth->logout();
		return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
	}
  
  public function redirectPath() {
		if (property_exists($this, 'redirectPath')) {
			return $this->redirectPath;
		}
		return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
	}
  
  public function loginPath()	{
		return property_exists($this, 'loginPath') ? $this->loginPath : '/auth/login';
	}
  
  protected function allowRegister() {
    return (!property_exists($this, 'allowRegister') || $this->allowRegister);
  }
  
  protected function loginView() {
    return property_exists($this, 'loginView') ? $this->loginView : 'auth.login';
  }
  
}
