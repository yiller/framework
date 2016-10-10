<?php namespace YitOS\Mongodb;

use Jenssegers\Mongodb\Queue\MongoConnector;
use Jenssegers\Mongodb\MongodbServiceProvider as BaseServiceProvider;

/**
 * 扩展MongoDB数据库连接（多数据库选择）
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Mongodb
 * @see \Jenssegers\Mongodb\MongodbServiceProvider
 * @see \Illuminate\Support\ServiceProvider
 */
class ServiceProvider extends BaseServiceProvider {
  
  public function register() {
    $this->app->resolving('db', function ($db) {
      $db->extend('mongodb', function ($config) {
        return new Connection($config);
      });
    });
    
    $this->app->resolving('queue', function ($queue) {
      $queue->addConnector('mongodb', function () {
        return new MongoConnector($this->app['db']);
      });
    });
  }
  
}
