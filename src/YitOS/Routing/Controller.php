<?php namespace YitOS\Routing;

use Config;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController {
  use DispatchesCommands, ValidatesRequests;
  
  public function __construct() {
    $this->setYitOSConfig()->loadYitOSValidator()->loadServiceProviders();
  }
  
  private function setYitOSConfig() {
    Config::set('auth.model', 'YitOS\Model\User');
    Config::set('yitos.name', 'YitOS通用版 - 史上最强的Laravel后端自助系统');
    return $this;
  }
  
  private function loadYitOSValidator() {
    $this->getValidationFactory()->resolver(function($translator, $data, $rules, $messages) {
      return new \YitOS\Validation\Validator($translator, $data, $rules, $messages);
    });
    return $this;
  }
  
  protected function loadServiceProviders() {
    return $this;
  }
  
}
