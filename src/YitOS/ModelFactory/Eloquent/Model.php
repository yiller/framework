<?php namespace YitOS\ModelFactory\Eloquent;

/**
 * 定义数据库模型接口
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Eloquent
 */
interface Model {
  
  /**
   * 初始化模型数据
   * @access public
   * @param string $entity
   * @param integer $duration
   * @return \YitOS\ModelFactory\Eloquent\Model
   */
  public function initial($entity, $duration);
  
  /**
   * 数据同步（下行）
   * @access public
   * @return bool
   */
  public function syncDownload();
  
}
