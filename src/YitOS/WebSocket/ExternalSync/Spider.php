<?php namespace YitOS\WebSocket\ExternalSync;

use RuntimeException;
use YitOS\WebSocket\Connector;
use YitOS\Contracts\WebSocket\ExternalSync\Model as ExternalSyncModelContract;

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
   * 根据html判断是否有更多列表页
   * @abstract
   * @access protected
   * @param string $html
   * @return bool
   */
  abstract protected function hasMore($html);
  
  /**
   * 获得页面的html
   * @access protected
   * @param string $url
   * @param string $type
   * @param integer $page
   * @return string
   * 
   * @throws RuntimeException
   */
  protected function html($url, $type, $page = 1) {
    if (!in_array($type, ['listings', 'detail'])) {
      throw new RuntimeException('非法调用');
    }
    $storage = app('filesystem')->disk('local');
    $id = $this->getExternalId($url, $type);
    if ($type == 'listings') {
      $file = 'connector/'.trim($this->getStorageDirectory(), '/').'/listings/'.$id.'-'.$page.'.html';
    } else {
      $file = 'connector/'.trim($this->getStorageDirectory(), '/').'/detail/'.$id.'.html';
    }
    if (!$storage->exists($file)) {
      $content = $this->catch(compact('url', 'id', 'type', 'page'));
      $content && $storage->put($file, $content);
    } else {
      $content = $storage->get($file);
    }
    return $content;
  }
  
  /**
   * 获得实体列表和下一页相关信息
   * @access public
   * @param ExternalSyncModelContract $model
   * @return array
   * 
   * @throws RuntimeException
   */
  public function listings(ExternalSyncModelContract $model) {
    $url = $model->getExternalUrl();
    $page = intval(app('request')->get('page'));
    $page < 1 && $page = 1;
    
    // 计算分类页面的扩展编号
    $category_id = $this->getExternalId($url, 'listings');
    if (!$category_id) {
      throw new RuntimeException('列表页面分析失败（未能获得扩展编号）');
    }
    $external = ['enabled' => 1, 'source' => $this->alias, 'url' => $url, 'id' => $category_id];
    // 抓取并保存页面
    $html = $this->html($url, 'listings', $page);
    // 根据列表页面解析元素
    $entities = [];
    // 是否还有更多分页
    if ($this->hasMore($html)) {
      $next = $page + 1;
      $handle  = "line_status('".$model->_id."', 'loading', ".json_encode([$category_id, '', '', '第 '.$page.' 页抓取成功，正在抓取第'.$next.'页']).");";
      $handle .= "listings('".$model->_id."',".($page+1).");";
    } else {
      throw new RuntimeException('列表完成');
    }
    return compact('external', 'entities', 'handle');
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
