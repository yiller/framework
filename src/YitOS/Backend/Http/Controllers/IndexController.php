<?php namespace YitOS\Backend\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;
use YitOS\Foundation\Auth\AuthenticatesAndRegistersUsers;

class IndexController extends Controller {
  use AuthenticatesAndRegistersUsers;
  
  public function __construct(Guard $auth) {
    parent::__construct();
    $this->auth = $auth;
    $this->allowRegister = false;
    $this->loginView = 'backend.account.login';
  }
  
  public function anyIndex() {
    return 'IndexController@index';
  }
  
}
