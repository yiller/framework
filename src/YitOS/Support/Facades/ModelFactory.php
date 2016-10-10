<?php namespace YitOS\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @author yiller <tech.yiller@yitos.cn>
 * @see \YitOS\ModelFactory\Manager
 */
class ModelFactory extends Facade {
  
  /**
   * 获得组件的注册名字
   * 
   * @access protected
   * @return string
   */
  protected static function getFacadeAccessor() {
    return 'model.factory';
  }
  
}
