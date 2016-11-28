<?php namespace YitOS\ModelFactory;

use InvalidArgumentException;
use Illuminate\Support\Manager as BaseManager;
use YitOS\ModelFactory\Drivers\Driver as DriverContract;

/**
 * 模型工厂类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory
 * @see \Illuminate\Support\Manager
 */
class Manager extends BaseManager {
  
  protected $options = [
    'MongoDB' => [ \YitOS\ModelFactory\Drivers\MongoDB::class, \YitOS\ModelFactory\Model\MongoDB::class ]
  ];
  
  /**
   * 获得默认的模型类名
   * 
   * @access public
   * @return string
   */
  public function getDefaultDriver() {
    return '';
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
    $config = $this->app['config']['model'];
    if (isset($config['default']) && is_string($config['default']) && isset($this->options[$config['default']])) {
      list($driver_class, $base_class) = $this->options[$config['default']];
    } elseif (isset($config['option']) && is_array($config['option']) && count($config['option']) == 2) {
      list($driver_class, $base_class) = $config['option'];
    } else {
      $driver_class = $base_class = '';
    }
    
    if (!$driver_class || !is_string($driver_class) || !class_exists($driver_class) || 
        !$base_class   || !is_string($base_class)   || !class_exists($base_class)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }

    $mapping = $this->app['config']['model.mapping'];
    if (!isset($mapping[$name]) || !$mapping[$name]) {
      $duration = 0;
      $model_class = $base_class;
      $enabledSync = false;
    } elseif (is_string($mapping[$name])) {
      $duration = 0;
      $model_class = $mapping[$name];
      $enabledSync = false;
    } elseif (is_array($mapping[$name])) {
      $model_class = $mapping[$name][0];
      $duration = count($mapping[$name]) > 1 ? intval($mapping[$name][1]) : 0;
      $enabledSync = count($mapping[$name]) > 2 ? boolval($mapping[$name][2]) : true;
    } else {
      $duration = 0;
      $model_class = $base_class;
      $enabledSync = false;
    }
    
    try {
      if (!$model_class || !class_exists($model_class) || !(new $model_class) instanceof $base_class) {
        throw new InvalidArgumentException;
      }
      $instance = new $driver_class($name, $model_class, $duration, $enabledSync);
      if (!$instance instanceof DriverContract) {
        throw new InvalidArgumentException;
      }
    } catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('name')));
    }
    
    $instance->syncDownload();
    return $instance;
  }

}
