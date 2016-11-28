<?php namespace YitOS\ModelFactory\Model;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use YitOS\Support\Relations\ParentChildrenTrait;

/**
 * MongoDB数据库模型基类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Model
 * @see \Jenssegers\Mongodb\Eloquent\Model
 * @see \YitOS\Support\Relations\ParentChildrenTrait
 */
class MongoDB extends Eloquent {
  use ParentChildrenTrait;
  
  /**
   * 所有属性都允许批量赋值
   * @var bool
   */
  protected static $unguarded = true;
  
}
