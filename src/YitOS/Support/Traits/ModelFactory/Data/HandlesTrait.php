<?php namespace YitOS\Support\Traits\ModelFactory\Data;

use YitOS\Support\Traits\BootstrapUI\DataHandlesTrait;

/**
 * 常规数据管理控制器分离类（数据处理）
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\ModelFactory\Data
 * @see \YitOS\Support\Traits\ModelFactory\Data\Config
 * @see \YitOS\Support\Traits\BootstrapUI\DataHandlesTrait
 * @see \YitOS\Support\Traits\BootstrapUI\CommonTrait
 */
trait HandlesTrait {
  use Config, DataHandlesTrait;
  
  protected $meta = null;
  
  /**
   * 根据meta模型获得列定义
   * @access protected
   * @param array $columns
   * @return array
   */
  protected function columnsConfigured($columns) {
    $__ = app('request')->input('__', '');
    $this->meta = app('db')->collection('_meta')->find($__);
    if (!$this->meta) return [];
    $elements = [];
    foreach ($this->meta['elements'] as $element) {
      $elements[$element['alias']] = [
        'section' => $this->meta['name'],
        'name'    => $element['alias'],
        'label'   => $element['name'],
        'bind'    => $element['alias'],
        'type'    => $element['structure'],
        'multi_language' => boolval($element['multi_language']),
      ];
    }
    return $elements;
  }
  
  public function import() {
    $steps = [
      ['开始导入', '下载示例表格或上传数据文件（目前只支持EXCEL）'], 
      ['文件分析', '选择表格页并核对数据列'], 
      ['执行导入', '数据保存'], 
      ['导入结果', '通知处理结果']
    ];
    $current = 0;
    $data = [
      'steps' => $steps,
      'col' => intval(12 / count($steps)),
      'current' => $current,
      'title' => '导入'.$this->meta['name'],
      'handle_url' => $this->handle_url,
    ];
    return view('ui::import.'.$current, $data);
  }
  
  public function export() {
    $steps = [
      ['开始导出', '下载历史导出数据或开始新的导出（目前只支持EXCEL）'], 
      ['导出条件', '导出条件设置'], 
      ['执行导出', '导出文件生成'], 
      ['导出结果', '通知处理结果并提供下载链接']
    ];
    
  }
  
}
