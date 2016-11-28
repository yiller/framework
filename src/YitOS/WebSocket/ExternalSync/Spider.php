<?php namespace YitOS\WebSocket\ExternalSync;

use RuntimeException;
use YitOS\WebSocket\Connector;

/**
 * 远程同步蜘蛛基类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket\ExternalSync
 * @see \YitOS\WebSocket\Connector
 */
abstract class Spider extends Connector {
  
  /**
   * 接口名称
   * @var string
   */
  public $label = '未知同步接口';
  
  /**
   * 接口别名
   * @var string
   */
  public $alias = 'unknown';
  
  /**
   * 当前应用程序
   * @var \Illuminate\Foundation\Application
   */
  protected $app;
  
  /**
   * 生成一个远程同步连接实例
   * @access public
   * @param \Illuminate\Foundation\Application $app
   * @return void
   */
  public function __construct($app) {
    $this->app = $app;
  }
  
  /**
   * 获得缓存文件目录
   * @abstract
   * @access protected
   * @return string
   */
  abstract protected function getStorageDirectory();
  
  /**
   * 根据URL提取实体扩展编号
   * @abstract
   * @access protected
   * @param string $url
   * @param string $type
   * @return string
   */
  abstract protected function getExternalId($url, $type = 'detail');
  
  /**
   * 获得存储引擎
   * @access protected
   * @return \Illuminate\Contracts\Filesystem\Filesystem
   */
  protected function getStorage() {
    return app('filesystem')->disk('local');
  }
  
  /**
   * 获得实体列表和下一页相关信息
   * @access public
   * @param string $url
   * @param integer $page
   * @return array
   */
  public function listings($url, $page = 1) {
    // 计算分类页面的扩展编号
    $category_id = $this->getExternalId($url, 'listings');
    if (!$category_id) {
      throw new \RuntimeException('列表页面分析失败（未能获得扩展编号）');
    }
    $category = ['external' => ['enabled' => 1, 'source' => $this->alias, 'url' => $url, 'id' => $category_id]];
    // 抓取并保存页面
    $file = 'connector/'.trim($this->getStorageDirectory(), '/').'/'.$category_id.'-'.$page.'.html';
    if (!$this->getStorage()->exists($file)) {
      $content = $this->catch(['url' => $url, 'id' => $category_id, 'type' => 'listings', 'page' => $page]);
      $content && $this->getStorage()->put($file, $content);
    } else {
      $content = $this->getStorage()->get($file);
    }
    // 根据列表页面解析元素
    $entities = [];
    // 是否还有更多分页
    $more = false;
    return compact('category', 'entities', 'more');
  }
  
  /**
   * 获得当前请求网关地址
   * @access protected
   * @param array $parameters
   * @return string
   * 
   * @throws RuntimeException
   */
  protected function getCatchGateway($parameters = []) {
    return $parameters['url'];
  }
  
  /**
   * 获得第三方扩展键名
   * @access protected
   * @return string
   * 
   * @throw \RuntimeException
   */
  protected function getTag() {
    if (!property_exists($this, 'options') || !is_array($this->options) || !isset($this->options['tag'])) {
      throw new RuntimeException(trans('websocket::exception.sync.connector_configurature_error'));
    }
    return $this->options['tag'];
  }
  
  /**
   * 获得第三方扩展标识
   * @access protected
   * @return string
   * 
   * @throw \RuntimeException
   */
  protected function getSource() {
    if (!property_exists($this, 'options') || !is_array($this->options) || !isset($this->options['source'])) {
      throw new RuntimeException(trans('websocket::exception.sync.connector_configurature_error'));
    }
    return $this->options['source'];
  }
  
  /**
   * 获得额外的配置参数
   * @access protected
   * @return array
   * 
   * @throw \RuntimeException
   */
  protected function getExtraParams() {
    if (!property_exists($this, 'options') || !is_array($this->options)) {
      throw new RuntimeException(trans('websocket::exception.sync.connector_configurature_error'));
    }
    return isset($this->options['params']) && is_array($this->options['params']) ? $this->options['params'] : [];
  }
  
}
