<?php namespace YitOS\WebSocket;

use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Str;

/**
 * 定义默认的连接者
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket
 */
class Connector {
  
  /**
   * 网关地址
   * @var string
   */
  protected $gateway = '';
  
  /**
   * 默认请求方式
   * @var string
   */
  protected $method = 'get';
  
  /**
   * HTTP请求返回代码
   * 
   * @var int 
   */
  protected $http_code = 0;
  
  /**
   * 最近一次连接错误信息
   * 
   * @var string 
   */
  protected $error = '';
  
  /**
   * 生成一个连接者实例
   * @access public
   * @param array|null $config
   * @return void
   */
  public function __construct($config) {
    if ($config && is_array($config)) {
      $this->initialize($config);
    }
  }
  
  /**
   * 初始化连接者参数
   * @access public
   * @param array|null $config
   * @return \YitOS\WebSocket\Connector
   * 
   * @throw InvalidArgumentException
   */
  public function initialize($config) {
    if (!$config || !is_array($config)) {
      throw new InvalidArgumentException(trans("exception.connector.invalidArguments"));
    }
    foreach ($config as $k => $v) $this->$k = $v;
    return $this;
  }
  
  /**
   * 获得当前请求网关地址
   * @access protected
   * @param string $name
   * @param array $parameters
   * @return string
   */
  protected function gateway($name = '', $parameters = []) {
    if (empty($name)) {
      return $this->gateway;
    }
    
    $method = 'get'.Str::studly($name).'Gateway';
    if (method_exists($this, $method)) {
      return $this->$method($parameters);
    }
    
    return $this->gateway.$name;
  }
  
  /**
   * 获得当前请求的请求方式
   * @access protected
   * @param string $name
   * @return string
   */
  protected function method($name = '') {
    if (empty($name)) {
      return strtolower($this->method);
    }
    
    $method = 'get'.Str::studly($name).'Method';
    if (method_exists($this, $method)) {
      return $this->$method();
    }
    
    return strtolower($this->method);
  }
  
  /**
   * 生成请求数据
   * @access protected
   * @param string $name
   * @param array $parameters
   * @return string
   */
  protected function beforeRequest($name, $parameters) {
    $method = 'before'.Str::studly($name).'Request';
    if (method_exists($this, $method)) {
      return $this->$method($parameters);
    }
    return http_build_query($parameters);
  }
  
  /**
   * 处理响应数据
   * @access protected
   * @param string $name
   * @param string $output
   * @return string
   */
  protected function afterRequest($name, $output) {
    $method = 'after'.ucfirst($name).'Request';
    if (method_exists($this, $method)) {
      return $this->$method($output);
    }
    return $output;
  }
  
  /**
   * 返回最近一次的请求错误
   * @access public
   * @return string
   */
  public function getLastError() {
    return '['.$this->http_code.'] '.$this->error;
  }
  
  /**
   * 动态调用方法
   * @access public
   * @param string $name
   * @param array $parameters
   * @return mixed
   * 
   * @throw \InvalidArgumentException
   * @throw \RuntimeException
   */
  public function __call($name, $parameters) {
    $url = $this->gateway($name, $parameters ? $parameters[0] : []);
    $method = $this->method($name);
    $request = $this->beforeRequest($name, $parameters ? $parameters[0] : []);
    if (extension_loaded('curl')) {
      $this->error = '';
      $ch = curl_init();
      if ($method == 'get') {
        $url = strpos($url, '?') === false ? $url.'?'.$request : $url.'&'.$request;
      } else {
        curl_setopt($ch, CURLOPT_POST,         true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,   $request);
      }
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_URL,            $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER,         false);
      curl_setopt($ch, CURLOPT_TIMEOUT,        300);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      if (property_exists($this, 'use_cookie') && $this->use_cookie) {
        $cookie = storage_path('app'.DIRECTORY_SEPARATOR.'connector'.DIRECTORY_SEPARATOR.parse_url($url)['host'].'.cookie.jar');
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
      }
      $output = curl_exec($ch);
      if (($this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) != 200) {
        $this->app->environment('local') && die($output);
        $output = '';
        $this->error = curl_error($ch);
      }
      curl_close($ch);
      unset($ch);
    } elseif ($method == 'get') {
      $url = strpos($url, '?') === false ? $url.'?'.$request : $url.'&'.$request;
      $output = file_get_contents($url);
    } else {
      throw new RuntimeException(trans('websocket::exception.module_not_installed'));
    }
    return $this->afterRequest($name, $output);
  }
  
}
