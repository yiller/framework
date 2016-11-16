<?php namespace YitOS\ModelFactory;

use InvalidArgumentException;
use Illuminate\Support\Manager as BaseManager;
use Illuminate\Database\Eloquent\Model as ModelContract;

/**
 * Mongodb模型工厂类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory
 * @see \Illuminate\Support\Manager
 */
class Manager extends BaseManager {
  
  protected $options = [
    'Mongo' => \YitOS\ModelFactory\Factories\MongoFactory::class,
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
    if (isset($this->customCreators[$driver])) {
      return $this->callCustomCreator($driver);
    }
    $factory = $this->getDefaultDriver();
    if (!is_string($factory)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('driver')));
    }
    if (isset($this->options[$factory])) {
      $factory = $this->options[$factory];
    }
    if (empty($factory) || !class_exists($factory)) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('driver')));
    }
    
    $mapping = $this->app['config']['model.mapping'];
    $classname = $driver;
    $duration = 0;
    if (array_key_exists($driver, $mapping)) {
      if (is_string($mapping[$driver])) {
        $classname = $mapping[$driver];
      } elseif (is_array($mapping[$driver])) {
        $classname = $mapping[$driver][0];
        (count($mapping[$driver]) > 1) && ($duration = intval($mapping[$driver][1]));
      }
    }
    
    try {
      $instance = new $factory($driver, $duration, $classname);
    } catch(InvalidArgumentException $e) {
      throw new InvalidArgumentException(trans('modelfactory::exception.mapping_not_found', compact('driver')));
    }
    $instance->syncDownload();
    return $instance;
  }

}
