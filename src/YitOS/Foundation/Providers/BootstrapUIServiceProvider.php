<?php namespace YitOS\Foundation\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * BootstrapUI服务供应者
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\Providers
 * @see \Illuminate\Support\ServiceProvider
 */
class BootstrapUIServiceProvider extends BaseServiceProvider {
  
  public function boot() {
    $this->publishes([
        __DIR__.'/../BootstrapUI/assets' => $this->app->basePath().'/public_html/assets/global',
    ], 'BootstrapUI');
    $this->loadViewsFrom(__DIR__.'/../BootstrapUI/views', 'ui');
    $this->loadTranslationsFrom(__DIR__.'/../BootstrapUI/lang', 'ui');
  }
  
  /**
   * 注册该服务提供者为单件对象
   *
   * @access public
   * @return void
   */
  public function register() {}
  
}
