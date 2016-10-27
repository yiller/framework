<?php namespace YitOS\WebSocket\Connectors;

use Carbon\Carbon;
use YitOS\WebSocket\Connector;
use YitOS\OpenSSL\DESCryptor;

/**
 * 定义YitOS的API连接类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket\Connectors
 * @see \YitOS\WebSocket\Connector
 */
class YitOS extends Connector {
  
  /**
   * 当前应用程序
   * @var \Illuminate\Foundation\Application
   */
  protected $app;
  
  /**
   * 网关地址
   * @var string
   */
  protected $gateway = 'http://api.yitos.cn:6326/';
  
  /**
   * 默认请求方式
   * @var string
   */
  protected $method = 'post';
  
  /**
   * 原始调用的方法名
   * @var string
   */
  protected $original_name;
  
  /**
   * 原始调用的参数
   * @var array 
   */
  protected $original_parameters;
  
  
  /**
   * 生成一个YitOS连接实例
   * @access public
   * @param \Illuminate\Foundation\Application $app
   * @return void
   */
  public function __construct($app) {
    $this->app = $app;
  }
  
  public function initialize($config) {}
  
  /**
   * 获得当前请求网关地址
   * @access protected
   * @param string $name
   * @return string
   */
  protected function gateway($name = '') {
    $url = $this->gateway;
    if (empty($name)) {
      return $url;
    }
    $url .= $name.'.json?_timestamp='.Carbon::now()->format('U');
    if ($name != 'connect' && $this->app['request']->session()->has('client_token')) {
      ($token = $this->app['request']->session()->get('client_token')) && ($url .= '&_token='.explode(':', $token)[0]);
    }
    return $url;
  }
  
  /**
   * 生成请求数据
   * @access protected
   * @param string $name
   * @param array $parameters
   * @return string
   */
  protected function beforeRequest($name, $parameters) {
    $parameters = $parameters ?: [];
    if ($name != 'connect') {
      $this->original_name = $name;
      $this->original_parameters = $parameters;
      if ($this->app['request']->session()->has('client_token')) {
        ($token = $this->app['request']->session()->get('client_token')) && ($parameters['_token'] = explode(':', $token)[0]);
      }
    }
    if ($parameters) {
      return $this->cryptor()->encrypt(json_encode($parameters));
    }
    return '';
  }
  
  /**
   * 解密响应数据
   * @access protected
   * @param string $name
   * @param string $output
   * @return string
   */
  protected function afterRequest($name, $output) {
    if ($output) {
      $response = json_decode($this->cryptor()->decrypt($output), true);
      if (!$response || !is_array($response) || !array_key_exists('code', $response)) {
        $this->app->environment('local') && die($output);
        return [];
      }
      if ($response['code'] == 8000) { // 客户端令牌失效
        $name = $this->original_name;
        $parameters = $this->original_parameters;
        return $this->token() ? ($parameters ? $this->{$name}($parameters) : $this->{$name}()) : [];
      }
      return $response;
    }
    return [];
  }
  
  /**
   * 获得RSA加密器
   * @access protected
   * @return \YitOS\OpenSSL\DESCryptor
   */
  protected function cryptor() {
    $secret_key = ($token = $this->app['request']->session()->get('client_token')) ? explode(':', $token)[1] : '';
    $cryptor = new DESCryptor($secret_key);
    return $cryptor;
  }
  
  /**
   * 获得客户端令牌
   * @access public
   * @return bool
   */
  protected function token() {
    $ip = $this->app['request']->ip();
    $response = $this->connect(compact('ip'));
    if ($response && $response['code'] == 1) {
      $token = implode(':', explode('|', $response['data']));
      $this->app['request']->session()->put('client_token', $token);
      return true;
    }
    return false;
  }
  
  /**
   * 判断客户端令牌是否有效
   * @access public
   * @return bool
   */
  public function tokenExists() {
    // 令牌存在
    if (!$this->app['request']->session()->has('client_token')) {
      return $this->token();
    }
    // 格式正确
    $token = $this->app['request']->session()->get('client_token', '');
    if (!$token || substr_count($token, ':') != 2) {
      return $this->release()->token();
    }
    // 时间有效
    $expires = explode(':', $token)[2];
    if (Carbon::createFromTimestamp($expires)->lt(Carbon::now())) {
      return $this->release()->token();
    }
    return true;
  }
  
  /**
   * 释放令牌
   * @access public
   * @return void
   */
  public function release() {
    $this->app['request']->session()->forget('client_token');
    return $this;
  }
  
}
