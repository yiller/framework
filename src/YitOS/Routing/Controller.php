<?php namespace YitOS\Routing;

use Config;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController {
  use DispatchesCommands, ValidatesRequests;
  
  public function __construct() {
    $this->loadYitOSValidator()->setYitOSConfig();
  }
  
  private function setYitOSConfig() {
    Config::set('auth.model', 'YitOS\Backend\Model\User');
    return $this;
  }
  
  private function loadYitOSValidator() {
    $this->getValidationFactory()->resolver(function($translator, $data, $rules, $messages) {
      return new \YitOS\Validation\Validator($translator, $data, $rules, $messages);
    });
    return $this;
  }
  
}
