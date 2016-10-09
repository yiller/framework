<?php namespace YitOS\BootstrapUI;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * BootstrapUI服务供应者
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\BootstrapUI
 * @see \Illuminate\Support\ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider {
  
  public function boot() {
    $this->publishes([
        __DIR__.'/assets' => $this->app->basePath().'/public_html/assets/global',
    ], 'BootstrapUI');
    $this->loadViewsFrom(__DIR__.'/views', 'ui');
    $this->loadTranslationsFrom(__DIR__.'/lang', 'ui');
  }
  
  /**
   * 注册该服务提供者为单件对象
   * @access public
   * @return void
   */
  public function register() {}
  
}
