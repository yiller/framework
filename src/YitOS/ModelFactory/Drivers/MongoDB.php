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
    return ($response && $response['code'] == 1) ? $response['data'] : [];
  }
  
  /**
   * 数据同步（下行）之后
   * @access protected
   * @param \YitOS\ModelFactory\Model\MongoDB $model
   * @return \YitOS\ModelFactory\Model\MongoDB|null
   */
  protected function downloaded($model) {
    if (!$model) {
      return null;
    }
    // 创建人信息
    $user = app('auth')->getProvider()->retrieveByCredentials(['id' => $model->account_id]);
    $model->account_id = $user ? $user->getAuthIdentifier() : '';
    $model->user = $user ? ['username' => $user->account_username,'realname' => $user->realname, 'mobile' => $user->mobile, 'team' => ['name' => $user->team['name'], 'alias' => $user->team['alias']]] : [];
    // 父子关系链
    $parent = $this->builder()->where('id', $model->parent_id)->first();
    $model->parent_id = $parent ? $parent->getKey() : '';
    $model->parent = $parent ? array_only($parent->toArray(), ['label', 'link', 'alias']) : [];
    $model->parents = $model->parents ? $this->builder()->whereIn('id', $model->parents)->pluck($model->getKeyName())->toArray() : [];
    $model->children = $model->children ? $this->builder()->whereIn('id', $model->children)->pluck($model->getKeyName())->toArray() : [];
    return $model->save() ? $model : null;
  }
  
}
