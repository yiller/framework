<?php namespace YitOS\ModelFactory\Model;

use Illuminate\Support\Facades\Cache;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use YitOS\Support\Relations\ParentChildrenTrait;

/**
 * MongoDB数据库模型基类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Model
 * @see \Jenssegers\Mongodb\Eloquent\Model
 * @see \YitOS\Support\Relations\ParentChildrenTrait
 */
class MongoDB extends Eloquent {
  use ParentChildrenTrait;
  
  /**
   * 数据同步的时间间隔（单位：秒），0代表永不同步
   * @var integer 
   */
  public $duration = 0;
  
  /**
   * 所有属性都允许批量赋值
   * @var bool
   */
  protected static $unguarded = true;
  
  /**
   * 追加的字段列表
   * @var array
   */
  protected $appends = ['parents', 'children'];
  
  /**
   * 获得模型关系缓存的名称
   * @access public
   * @return string
   */
  protected function IDXFilename() {
    return $this->getTable().'_relations';
  }
  
  /**
   * 清除模型的缓存索引
   * @access public
   * @param string $name
   * @return void
   */
  public function IDXClear($name = '') {
    if (!$name) {
      Cache::forget($this->IDXFilename());
      return;
    }
    $indexes = Cache::get($this->IDXFilename(), []);
    if (array_key_exists($name, $indexes)) {
      unset($indexes[$name]);
      Cache::forever($this->IDXFilename(), $indexes);
    }
  }
  
  /**
   * 写入索引数据
   * @access protected
   * @param string $name
   * @param mixed $value
   * @return void
   */
  protected function IDXUpdate($name, $value) {
    if (!$name) {
      return;
    }
    $indexes = Cache::get($this->IDXFilename(), []);
    if (!isset($indexes[$name])) {
      $indexes[$name] = [];
    }
    $indexes[$name][$this->getKey()] = $value;
    Cache::forever($this->IDXFilename(), $indexes);
  }
  
  /**
   * 根据名称获得对象缓存索引
   * @access protected
   * @param string $name
   * @param mixed $default
   * @return mixed
   */
  protected function IDX($name, $default = null) {
    $indexes = Cache::get($this->IDXFilename(), []);
    if (!isset($indexes[$name][$this->getKey()])) {
      return $default;
    }
    return $indexes[$name][$this->getKey()];
  }
  
  /**
   * 获得父节点列表
   * @access public
   * @return array
   */
  public function getParentsAttribute() {
    $parents = $this->IDX('parents', []);
    if ($parents) {
      return $parents;
    }
    if ($this->parent_id) {
      $parents = $this->rel_parent->parents;
      $parents[] = $this->rel_parent->getKey();
      $this->IDXUpdate('parents', $parents);
    }
    return $parents;
  }
  
  /**
   * 获得所有子节点
   * @access public
   * @return array
   */
  public function getChildrenAttribute() {
    $children = $this->IDX('children', []);
    if ($children) {
      return $children;
    }
    foreach ($this->rel_children as $child) {
      $children = array_unique(array_merge($children, [$child->getKey()], $child->children));
    }
    $this->IDXUpdate('children', $children);
    return $children;
  }
  
}
