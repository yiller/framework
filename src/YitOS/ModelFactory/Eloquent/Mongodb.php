<?php namespace YitOS\ModelFactory\Eloquent;

use Jenssegers\Mongodb\Eloquent\Model as BaseModel;
use YitOS\Support\Relations\ParentChildrenTrait;
use YitOS\ModelFactory\Eloquent\Model as ModelContract;

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
abstract class Mongodb extends BaseModel implements ModelContract {
  use ParentChildrenTrait;
  
  /**
   * 所有属性都允许批量赋值
   * @var bool
   */
  protected static $unguarded = true;

}
