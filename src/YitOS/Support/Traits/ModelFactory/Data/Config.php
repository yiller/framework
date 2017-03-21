<?php namespace YitOS\Support\Traits\ModelFactory\Data;

/**
 * 常规数据管理配置分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\ModelFactory\Data
 */
trait Config {
  
  /**
   * 定义实体名称
   * @var string
   */
  protected $name = '常规数据';
  
  /**
   * 获得数据源的查询构造器（公用）
   * @access protected
   * @return \Illuminate\Database\Query\Builder
   */
  protected function builder() {
    return M('jd_feedback_orders')->builder();
  }
  
  /**
   * 常规数据的操作定义
   * @access protected
   * @return array
   */
  protected function handles() {
    return [
      ['handle' => 'import'],
      ['handle' => 'export'],
    ];
  }
  
}
