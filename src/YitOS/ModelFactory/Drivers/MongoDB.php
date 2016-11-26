<?php namespace YitOS\ModelFactory\Drivers;

use YitOS\Support\Facades\WebSocket;
use YitOS\ModelFactory\Drivers\Driver as SyncDriver;

/**
 * MongoDB数据库模型工厂类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Drivers
 * @see \YitOS\ModelFactory\Drivers\Driver
 */
class MongoDB extends SyncDriver {
  
  /**
   * 元素定义
   * @access protected
   * @return array
   */
  protected function getElements() {
    $response = WebSocket::sync_elements(['name' => $this->name()]);
    return $response && $response['code'] == 1 ? $response['elements'] : [];
  }
  
  /**
   * 获得SQLBuilder
   * @access public
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function builder() {
    $model_class = $this->classname;
    $instance = new $model_class;
    return $instance->newQuery();
  }
  
  /**
   * 获得同步配置表
   * @access protected
   * @return \Jenssegers\Mongodb\Collection
   */
  public function sync_config_table() {
    return app('db')->collection('_sync');
  }
  
  /**
   * 数据同步（下行）
   * @access public
   * @return bool
   */
  protected function download($timestamp) {
    $params = ['name' => $this->name()];
    if ($timestamp > 0) {
      $params['timestamp'] = $timestamp;
    }
    $response = WebSocket::sync_download($params);
    dd($response);
  }
  
}
