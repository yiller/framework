<?php namespace YitOS\Mongodb;

use Jenssegers\Mongodb\Connection as BaseConnection;

/**
 * 扩展MongoDB数据库连接（多数据库选择）
 * 
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Mongodb
 * @see \Jenssegers\Mongodb\Connection
 * @see \Illuminate\Support\ServiceProvider
 */
class Connection extends BaseConnection {
  
  /**
   * 原始配置参数
   * @var array
   */
  protected $original_config = [];
  
  /**
   * 数据库选择
   * @access public
   * @param string $database
   * @return void
   */
  public function selectDatabase($database) {
    if (empty($this->original_config)) {
      $this->original_config = $this->config;
    }
    $config = $this->original_config;
    $config['database'] .= '_'.$database;
    parent::__construct($config);
  }
  
}
