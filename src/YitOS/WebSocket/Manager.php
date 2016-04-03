<?php namespace YitOS\WebSocket;

use Illuminate\Support\Str;
use Illuminate\Support\Manager as BaseManager;

/**
 * WebSocket Factory类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket
 * @see \Illuminate\Support\Manager
 */
class Manager extends BaseManager {
  
  /**
   * 获得默认的连接名称
   * 
   * @return string
   */
  public function getDefaultDriver() {
    return $this->app['config']['websocket.default'];
  }
  
  /**
   * 创建一个新的WebSocket驱动实例
   * 
   * @access protected
   * @param string $driver
   * @return mixed
   */
  protected function createDriver($driver) {
    $method = 'create'.Str::studly($driver).'Driver';
    if (isset($this->customCreators[$driver])) {
      return $this->callCustomCreator($driver);
    } elseif (method_exists($this, $method)) {
      return $this->$method();
    } else {
      return $this->makeDefaultConnector($driver);
    }
  }
  
  /**
   * 创建默认的WebSocket连接者
   * 
   * @access protected
   * @param string $driver
   */
  protected function makeDefaultConnector($driver) {
    $connector = new Connector($this->app['config']['websocket.connectors.'.$driver]);
    return $connector;
  }
  
}
