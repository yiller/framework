<?php namespace YitOS\Support\Relations;

/**
 * 自身关联
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Relations
 */
trait ParentChildrenTrait {
  
  /**
   * 下级关联
   * @access public
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function rel_children() {
    return $this->hasMany('\\'.get_class($this), 'parent_id');
  }
  
  /**
   * 上级关联
   * @access public
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function rel_parent() {
    return $this->belongsTo('\\'.get_class($this), 'parent_id');
  }
  
}
