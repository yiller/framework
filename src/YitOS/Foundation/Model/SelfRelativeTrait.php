<?php namespace YitOS\Foundation\Model;

/**
 * 自身关联
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\Model
 */
trait SelfRelativeTrait {
  
  /**
   * 下级地址
   * 
   * @access public
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function rel_children() {
    return $this->hasMany('\\'.get_class($this), 'parent_id', 'id');
  }
  
  /**
   * 上级地址
   * 
   * @access public
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function rel_parent() {
    return $this->belongsTo('\\'.get_class($this), 'parent_id');
  }
  
}
