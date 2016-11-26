<?php namespace YitOS\ModelFactory;

use InvalidArgumentException;
use Illuminate\Support\Manager as BaseManager;
use YitOS\ModelFactory\Drivers\Driver as DriverContract;

/**
 * Mongodb模型工厂类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory
 * @see \Illuminate\Support\Manager
 */
class Manager extends BaseManager {
  
  protected $options = [
    'Mongodb' => \YitOS\ModelFactory\Drivers\MongoDB::class,
  ];
  
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
    $name = $driver;
    $driver = $this->getDefaultDriver();
    if (!$driver || !is_string($driver)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }
    
    $classname = isset($this->options[$driver]) ? $this->options[$driver] : $driver;
    if (!$classname || !class_exists($classname)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }
    
    $mapping = $this->app['config']['model.mapping'];
    
    if (!isset($mapping[$name]) || !$mapping[$name]) {
      $duration = 0;
      $model_class = $name;
      $enabledSync = false;
    } elseif (is_string($mapping[$name])) {
      $duration = 0;
      $model_class = $mapping[$name];
      $enabledSync = true;
    } elseif (is_array($mapping[$name])) {
      $model_class = $mapping[$name][0];
      $duration = count($mapping[$name]) > 1 ? intval($mapping[$name][1]) : 0;
      $enabledSync = count($mapping[$name]) > 2 ? boolval($mapping[$name][2]) : true;
    } else {
      $duration = 0;
      $model_class = $name;
      $enabledSync = false;
    }
    
    if (!$model_class) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }
    
    try {
      $instance = new $classname($name, $model_class, $duration, $enabledSync);
    } catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }
    
    if (!$instance instanceof DriverContract) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }
    
    $instance->syncDownload();
    return $instance;
  }

}
