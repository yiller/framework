<?php namespace YitOS\Backend\Http\Controllers;

use Config;
use YitOS\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
  
  public function __construct() {
    parent::__construct();
    
    $this->middleware('acl');
  }
  
  protected function loadServiceProviders() {
    parent::loadServiceProviders();
    
    $providers = config('app.providers');
    $providers[] = 'YitOS\Backend\Providers\ComposerServiceProvider';
    Config::set('app.providers', $providers);
    
    return $this;
  }
  
}
