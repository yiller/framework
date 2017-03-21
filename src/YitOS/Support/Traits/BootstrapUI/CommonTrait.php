<?php namespace YitOS\Support\Traits\BootstrapUI;

use RuntimeException;

/**
 * BootstrapUI 公用分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\BootstrapUI
 */
trait CommonTrait {
  
  /**
   * 初始化配置
   * @access public
   * @return void
   * 
   * @throws RuntimeException
   */
  public function __construct() {
    // 检查控制器关键成员是否定义
    if (!property_exists($this, 'name') || !property_exists($this, 'route_prefix') || !method_exists($this, 'builder')) {
      throw new RuntimeException(trans('ui::exception.configuration_not_supported'));
    }
    // 列定义
    $columns = [];
    if (property_exists($this, 'elements')) {
      foreach ($this->elements as $element) {
        $columns[$element['alias']] = [
          'name'  => $element['alias'],
          'label' => $element['name'],
          'bind'  => $element['alias'],
          'type'  => $element['structure'],
          'multi_language' => isset($element['multi_language']) ? boolval($element['multi_language']) : false,
        ];
      }
    } elseif (method_exists($this, 'elements')) {
      foreach ($this->elements() as $element) {
        $columns[$element['alias']] = [
          'name'  => $element['alias'],
          'label' => $element['name'],
          'bind'  => $element['alias'],
          'type'  => $element['structure'],
          'multi_language' => isset($element['multi_language']) ? boolval($element['multi_language']) : false,
        ];
      }
      $columns['parent_id'] = ['name' => 'parent_id', 'label' => '上级分类', 'type' => 'parent_id', 'extra' => ['class' => 'input-medium']];
      $columns['sort_order'] = ['name' => 'sort_order', 'label' => '排列序号', 'type' => 'integer', 'extra' => ['class' => 'input-xsmall'], 'helper' => '排列序号越小越靠前'];
    }
    $columns = method_exists($this, 'columnsConfigured') ? $this->columnsConfigured($columns) : $columns;
    if (!$columns) {
      throw new RuntimeException(trans('ui::exception.configuration_not_supported'));
    }
    $this->columns = $columns;
    // 数据处理句柄
    if (method_exists($this, 'handles')) {
      $this->handles = $this->handles();
    } elseif (!property_exists($this, 'handles')) {
      $this->handles = [];
    }
    $handles = [];
    foreach ($this->handles as $item) {
      if (!isset($item['handle']) || !$item['handle'] || !is_string($item['handle'])) continue;
      $route = isset($item['route']) && $item['route'] && is_string($item['route']) ? $item['route'] : $item['handle'];
      $route = $this->route_prefix.'.'.$route;
      
      if (!app('routes')->hasNamedRoute($route)) continue;
      
      $alias = isset($item['angular_route']) && $item['angular_route'] && is_string($item['angular_route']) ? $item['angular_route'] : preg_replace('/\./i', '_', $route);
      $item['page'] = M('backend_menu')->builder()->where('alias', $alias)->first();
      $item['route'] = $route;
      $handles[$item['handle']] = $item;
    }
    
    // 数据表是否具备增加功能
    $this->enabled_add = array_key_exists('add', $handles);
    $this->page_add = $this->enabled_add ? $handles['add']['page'] : null;
    unset($handles['add']);
    // 是否具备导入导出功能
    $this->enabled_import = array_key_exists('import', $handles);
    $this->enabled_export = array_key_exists('export', $handles);
    unset($handles['import'], $handles['export']);
    $this->handles = $handles;
    // 数据表是否具备处理功能
    $this->enabled_handles = !empty($this->handles);
    
    method_exists($this, 'initial') && $this->initial();
  }
  
}
