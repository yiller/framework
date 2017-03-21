<?php namespace YitOS\Support\Traits\ModelFactory\Cache;

use Carbon\Carbon;
use YitOS\Support\Traits\BootstrapUI\DataTableTrait;

/**
 * 数据缓存管理控制器分离类（入口）
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\ModelFactory\Cache
 * @see \YitOS\Support\Traits\ModelFactory\Cache\Config
 * @see \YitOS\Support\Traits\BootstrapUI\DataTableTrait
 * @see \YitOS\Support\Traits\BootstrapUI\CommonTrait
 */
trait MainTrait {
  use Config, DataTableTrait;
  
  /**
   * 列表项额外配置（结构树和数据表）
   * @access protected
   * @param array $columns
   * @return array
   */
  protected function columnsConfigured($columns) {
    $columns = array_only_by_sort($columns, ['name','alias','model','built_in','duration','synchronized_at']);
    $options = [
      'name' => ['width' => '16%'],
      'alias'  => ['width' => '10%'],
      'model' => ['width' => '16%'],
      'duration' => ['width' => '10%', 'align' => 'right', 'handle' => function($v,$el){return $v?:'无需同步';}],
      'built_in' => ['width' => '10%', 'align' => 'center', 'handle' => function($v,$el){return $v?'Y':'N';}],
      'synchronized_at' => ['width' => '10%', 'align' => 'right', 'handle' => function($v,$el){return $el['duration']?($v?Carbon::createFromTimestamp($v)->format('Y-m-d H:i:s'):'尚未同步'):'-';}],
    ];
    return array_replace_recursive($columns, $options);
  }
  
}
