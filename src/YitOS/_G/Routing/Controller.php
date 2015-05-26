<?php namespace YitOS\_G\Routing;

use Config;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController {
  use DispatchesCommands, ValidatesRequests;
  
  public function __construct() {
    $this->setYitOSConfig()->loadYitOSValidator();
  }
  
  /**
   * 重写固定配置
   * 
   * @return \YitOS\_G\Routing\Controller
   */
  private function setYitOSConfig() {
    $config = $this->registriedConfig();
    foreach ($config as $key => $val) Config::set($key, $val);
    
    return $this;
  }
  
  /**
   * 加载自定义验证器
   * 
   * @return \YitOS\_G\Routing\Controller
   */
  private function loadYitOSValidator() {
    $this->getValidationFactory()->resolver(function($translator, $data, $rules, $messages) {
      return new \YitOS\_G\Validation\Validator($translator, $data, $rules, $messages);
    });
    return $this;
  }
  
  /**
   * 固定配置
   * 
   * @return array
   */
  protected function registriedConfig() {
    return [];
  }
  
}
