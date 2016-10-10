<?php namespace YitOS\ModelFactory;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Mongodb模型衍生服务供应者
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory
 * @see \Illuminate\Support\ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider {
  
  public function boot() {
    $this->loadTranslationsFrom(__DIR__.'/lang', 'modelfactory');
  }
  
  /**
   * 注册该服务提供者为单件对象
   * @access public
   * @return void
   */
  public function register() {
    $this->app->singleton('model.factory', function($app) {
      return new Manager($app);
    });
  }
  
}
