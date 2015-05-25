<?php namespace YitOS\Backend\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use YitOS\Foundation\Auth\AuthenticatesAndRegistersUsers;

class IndexController extends Controller {
  use AuthenticatesAndRegistersUsers;
  
  public function __construct(Guard $auth) {
    parent::__construct();
    $this->auth = $auth;
    $this->allowRegister = false;
  }
  
  public function getIndex() {
    $enterprise = $this->auth->user()->enterprise;
    if (!$enterprise) abort(404);
    return redirect(route('yitos.backend.dashboard', ['company' => $enterprise->slug]));
  }
  
}
