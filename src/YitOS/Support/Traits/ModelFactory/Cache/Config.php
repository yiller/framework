<?php namespace YitOS\Support\Traits\ModelFactory\Cache;

/**
 * 缓存管理配置分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\ModelFactory\Cache
 */
trait Config {
  
  /**
   * 定义实体名称
   * @var string
   */
  protected $name = '数据缓存';
  
  /**
   * 允许增加数据结构
   * @var bool
   */
  protected $enabled_add = true;
  
  /**
   * 元素定义
   * @var array 
   */
  protected $elements = [
    ['name' => '数据名称',    'alias' => 'name',            'structure' => 'string'],
    ['name' => '数据别名',    'alias' => 'alias',           'structure' => 'slug'],
    ['name' => '映射模型',    'alias' => 'model',           'structure' => 'string'],
    ['name' => '内建结构',    'alias' => 'built_in',        'structure' => 'boolean'],
    ['name' => '同步间隔',    'alias' => 'duration',        'structure' => 'integer'],
    ['name' => '最后同步时间', 'alias' => 'synchronized_at', 'structure' => 'datetime']
  ];
  
  /**
   * 获得数据源的查询构造器（公用）
   * @access protected
   * @return \Illuminate\Database\Query\Builder
   */
  protected function builder() {
    return app('db')->collection('_meta');
  }
  
  /**
   * 缓存数据记录的操作定义
   * @access protected
   * @return array
   */
  protected function handles() {
    return [
      ['handle' => 'add'],
      ['handle' => 'import_data', 'label' => '导入', 'icon' => 'fa fa-cloud-upload', 'color' => 'blue'],
      ['handle' => 'export_data', 'label' => '导出', 'icon' => 'fa fa-cloud-download', 'color' => 'blue'],
      ['handle' => 'rebuild', 'label' => '重建缓存', 'icon' => 'fa fa-refresh', 'color' => 'dark', 'mode' => 'modal'],
      ['handle' => 'delete', 'label' => '删除', 'icon' => 'fa fa-trash', 'color' => 'red-mint', 'mode' => 'modal', 'enabled' => function($el){return !$el['built_in'];}],
    ];
  }
  
}
