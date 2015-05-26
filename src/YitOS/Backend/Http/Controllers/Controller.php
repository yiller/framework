<?php namespace YitOS\Backend\Http\Controllers;

use YitOS\_G\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
  
  protected $validCompany = true;
  
  public function __construct() {
    parent::__construct();
    
    $this->middleware('acl');
    if ($this->validCompany) $this->middleware('company');
  }
  
  protected function registriedConfig() {
    return [
      'yitos.backend.name' => 'YitOS通用版 - 史上最强的Laravel后端自助系统',
      
      'auth.views.login' => 'yitos.backend.account.login',
      'auth.routes.login' => 'yitos.backend.login',
      'auth.routes.after_login' => 'yitos.backend.redirect',
      'auth.routes.logout' => 'yitos.backend.logout',
      'auth.routes.after_logout' => 'yitos.backend.login',
    ];
  }
  
}
