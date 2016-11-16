<?php namespace YitOS\ModelFactory\Factories;

/**
 * Mongodb数据库模型基类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Eloquent
 * @abstract
 * @see \YitOS\ModelFactory\Eloquent\Factories
 * @see \Jenssegers\Mongodb\Eloquent\Model
 * @see \Illuminate\Database\Eloquent\Model
 */
class MongoFactory extends Factory {
  
  /**
   * 所有属性都允许批量赋值
   * @var bool
   */
  protected static $unguarded = true;
  
  /**
   * 获得同步配置表
   * @access protected
   * @return \Jenssegers\Mongodb\Collection
   */
  protected function tableSync() {
    return app('db')->collection('_sync');
  }
  
  /**
   * 获得同步日志表
   * @abstract
   * @access protected
   * @return \Jenssegers\Mongodb\Collection
   */
  protected function tableLogs() {
    return app('db')->collection('_sync_logs');
  }

}
