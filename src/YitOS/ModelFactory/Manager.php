<?php namespace YitOS\ModelFactory;

use InvalidArgumentException;
use Illuminate\Support\Manager as BaseManager;
use YitOS\ModelFactory\Eloquent\Model as ModelContract;

/**
 * Mongodb模型工厂类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory
 * @see \Illuminate\Support\Manager
 */
class Manager extends BaseManager {
  
  /**
   * 获得默认的模型类名
   * 
   * @access public
   * @return string
   */
  public function getDefaultDriver() {
    return $this->app['config']['model.default'];
  }
  
  /**
   * 创建一个新的模型实例
   * 
   * @access protected
   * @param string $driver
   * @return \YitOS\ModelFactory\Eloquent\Model
   * 
   * @throws \InvalidArgumentException
   */
  protected function createDriver($driver) {
    if (isset($this->customCreators[$driver])) {
      return $this->callCustomCreator($driver);
    }
    $mapping = $this->app['config']['model.mapping'];
    $classname = $driver;
    $duration = 0;
    $initial = false;
    if (array_key_exists($driver, $mapping)) {
      if (is_string($mapping[$driver])) {
        $classname = $mapping[$driver];
        $initial = true;
      } elseif (is_array($mapping[$driver]) && count($mapping[$driver]) > 1) {
        $classname = $mapping[$driver][0];
        $duration = intval($mapping[$driver][1]);
        $initial = true;
      } elseif (is_array($mapping[$driver])) {
        $classname = $mapping[$driver][0];
        $initial = true;
      }
    }
    
    if (!class_exists($classname)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('classname')));
    }
    
    $instance = new $classname();
    if (!($instance instanceof ModelContract)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('classname')));
    }
    
    $initial && $instance->initial($driver, $duration);
    $instance->syncDownload();
    return $instance;
  }

}
