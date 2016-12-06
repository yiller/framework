<?php namespace YitOS\ModelFactory\Drivers;

use YitOS\Support\Facades\WebSocket;
use YitOS\ModelFactory\Drivers\Driver as SyncDriver;
use YitOS\ModelFactory\Model\MongoDB as ModelContract;

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
    return $this->instance()->newQuery();
  }
  
  /**
   * 获得同步配置表
   * @access protected
   * @return \Jenssegers\Mongodb\Collection
   */
  public function _sync() {
    return app('db')->collection('_sync');
  }
  
  /**
   * 数据同步（上行）
   * @access protected
   * @param array $data
   * @return array
   */
  protected function upload($data) {
    $params = ['name' => $this->name(), 'data' => $data];
    $response = WebSocket::sync_upload($params);
    return ($response && $response['code'] == 1) ? array_only($response, ['data','related']) : [];
  }
  
  /**
   * 数据同步（下行）
   * @access public
   * @return array
   */
  protected function download($timestamp) {
    $params = ['name' => $this->name()];
    if ($timestamp > 0) {
      $params['timestamp'] = $timestamp;
    }
    $response = WebSocket::sync_download($params);
    return ($response && $response['code'] == 1) ? $response['data'] : [];
  }
  
  /**
   * 数据同步之后
   * @access protected
   * @param ModelContract $model
   * @return ModelContract|null
   */
  protected function synchronized(ModelContract $model) {
    // 创建人信息
    $user = app('auth')->getProvider()->retrieveByCredentials(['id' => $model->account_id]);
    $model->account_id = $user ? $user->getAuthIdentifier() : '';
    $model->account = $user ? ['username' => $user->account_username,'realname' => $user->realname, 'mobile' => $user->mobile, 'team' => ['name' => $user->team['name'], 'alias' => $user->team['alias']]] : [];
    // 父子关系链
    $parent = $this->builder()->where('id', $model->parent_id)->first();
    $model->parent_id = $parent ? $parent->getKey() : '';
    $model->parent = $parent ? array_only($parent->toArray(), ['label', 'link', 'alias']) : [];
    // 清除缓存
    $model->IDXClear();
    return $model->save() ? $model : null;
  }
  
}
