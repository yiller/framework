<?php namespace YitOS\_G\Foundation\Auth;

use Illuminate\Http\Request;

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
  
  /**
   * 用户注册
   * 
   * @param Request $request  请求对象
   * @return void
   */
  public function anyRegister(Request $request) {
    if ($request->method() === 'POST') {
      $validator = $this->registrar->validator($request->all());
      if ($validator->fails()) {
        $this->throwValidationException($request, $validator);
      }
      $this->auth->login($this->registrar->create($request->all()));
      return redirect($this->redirectPath());
    }
    return view('auth.register');
  }
  
  /**
   * 用户登录
   * 
   * @param Request $request  请求对象
   * @return void
   */
  public function anyLogin(Request $request) {
    if ($request->method() === 'POST') {
      $this->validate($request, YitOS_RulesConvert($this->loginRules)->get('rules'), YitOS_RulesConvert($this->loginRules)->get('messages'));
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
      'config' => YitOS_RulesConvert($this->loginRules, 'jquery')
    ]);
  }
  
  /**
   * 登录失败提示字符串
   * 
   * @return string
   */
  protected function getFailedLoginMessage() {
		return '用户名或密码错误！';
	}
  
  /**
   * 用户登出成功后
   * 跳转到成员变量 afterLogoutRoute 指定的路由
   * 否则，跳转到配置 auth.routes.after_logout 指定的路由
   * 
   * @return void
   */
  public function anyLogout() {
		$this->auth->logout();
		return redirect(route(property_exists($this, 'afterLogoutRoute') ? $this->afterLogoutRoute : config('auth.routes.after_logout')));
	}
  
  /**
   * 用户登录成功之后
   * 跳转到成员变量 afterLoginRoute 指定的路由
   * 否则，跳转到配置 auth.routes.after_login 指定的路由
   * 
   * @return string
   */
  public function redirectPath() {
    return route(property_exists($this, 'afterLoginRoute') ? $this->afterLoginRoute : config('auth.routes.after_login'));
	}
  
  /**
   * 用户登录窗口和逻辑处理的地址
   * 如果定义成员变量 loginRoute，则用成员变量定义的路由地址
   * 否则，使用配置 auth.routes.login 的路由地址
   * 
   * @return string
   */
  public function loginPath()	{
		return route(property_exists($this, 'loginRoute') ? $this->loginRoute : config('auth.routes.login'));
	}
  
  /**
   * 是否允许用户注册
   * 
   * @return boolean
   */
  protected function allowRegister() {
    return (!property_exists($this, 'allowRegister') || $this->allowRegister);
  }
  
  /**
   * 用户登录窗口视图
   * 
   * @return string
   */
  protected function loginView() {
    return property_exists($this, 'loginView') ? $this->loginView : config('auth.views.login');
  }
  
}
