<?php namespace YitOS\WebSocket;

use RuntimeException;

/**
 * 远程同步连接者
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket
 */
abstract class SyncConnector extends Connector {
  
  /**
   * 接口名称
   * @var string
   */
  public $label = '未知同步接口';
  
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
  
  /**
   * 获得当前请求网关地址
   * 
   * @access protected
   * @param string $name
   * @return string
   * 
   * @throw \RuntimeException
   */
  protected function gateway($name = '') {
    if (empty($name)) {
      throw new RuntimeException(trans('websocket::exception.sync.interface_not_supported', ['interface' => 'none']));
    }
    $gateway = '';
    if (is_array($this->gateway)) {
      $gateway = isset($this->gateway[$name]) ? $this->gateway[$name] : '';
    }
    if (!$gateway) {
      throw new RuntimeException(trans('websocket::exception.sync.interface_not_supported', ['interface' => $name]));
    }
    return $gateway;
  }
  
  /**
   * 生成获得商品列表的请求数据
   * @access protected
   * @param string $name
   * @param array $parameters
   * @return string
   */
  protected function beforeRequest($name, $parameters) {
    $parameters = array_merge($this->getExtraParams(), $parameters);
    $mapping = [];
    if (property_exists($this, 'mapping') && isset($this->mapping[$name]) && is_array($this->mapping[$name])) {
      $mapping = $this->mapping[$name];
    }
    
    $parameters['id'] = isset($parameters['id']) ? trim($parameters['id']) : '';
    $parameters['page'] = isset($parameters['page']) ? intval($parameters['page']) : 1;
    $parameters['page'] = max($parameters['page'], 1);
    
    $params = [];
    foreach ($parameters as $key => $value) {
      $key = isset($mapping[$key]) ? $mapping[$key] : $key;
      $params[$key] = $value;
    }
    return http_build_query($params);
  }
  
  /**
   * 获得列表
   * @access protected
   * @param string $output
   * @return array
   */
  abstract protected function afterListingsRequest($output);
  
  /**
   * 获得详情
   * @abstract
   * @access protected
   * @param string $output
   * @return string
   */
  abstract protected function afterDetailRequest($output);
  
}
