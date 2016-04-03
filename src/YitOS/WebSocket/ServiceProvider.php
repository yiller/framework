<?php namespace YitOS\WebSocket;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * WebSocket服务供应者
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket
 * @see \Illuminate\Support\ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider {
  
  /**
   * 该服务延迟加载
   * 
   * @var bool
   */
  protected $defer = true;
  
  /**
   * 注册该服务提供者为单件对象
   *
   * @access public
   * @return void
   */
  public function register() {
    $this->app->singleton('websocket', function($app) {
      return new Manager($app);
    });
  }
  
}
