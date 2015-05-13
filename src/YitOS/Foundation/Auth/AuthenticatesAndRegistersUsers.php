<?php namespace YitOS\Foundation\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;

trait AuthenticatesAndRegistersUsers {
  
  protected $auth;
  protected $registrar;
  protected $loginRules = [
    'username' => [
      ['name' => 'required', 'message' => '请输入用户名！'],
      ['name' => 'alpha_dash', 'message' => '用户名只能包含数字、字母、下划线（_）和破折号（-）！']
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
    return view($this->loginView());
  }
  
  public function postLogin(Request $request) {
    $this->validate($request, [
			'username' => 'required|alpha_dash', 'password' => 'required',
		]);
    $credentials = $request->only('username', 'password');
    if ($this->auth->attempt($credentials, $request->has('remember'))) {
			return redirect()->intended($this->redirectPath());
		}
    return redirect($this->loginPath())
            ->withInput($request->only('email', 'remember'))
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
