<?php namespace YitOS\Contracts\DataTable;

/**
 * 数据表模型接口
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Contracts\DataTable
 */
interface Model {
  
  /**
   * 获得实体名称
   * 
   * @access public
   * @return string
   */
  public function getEntityName();
  
}
