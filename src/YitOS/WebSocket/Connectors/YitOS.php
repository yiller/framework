<?php namespace YitOS\WebSocket\Connectors;

use Carbon\Carbon;
use YitOS\WebSocket\Connector;
use YitOS\OpenSSL\RSACryptor;

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
   * 加密密钥
   * @var string 
   */
  protected $encrypt_key = '-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAoCbU4rk00rbqkb9l7Nr/oboMUAPd/9vi3d8TVKPsjKAIgYrD
dh8raYC+ByWfV5n9FSNi1afbrhUVmRD2kDpGNQs5Pdc5ljoERoBlCJ2w1b2rn5nT
QSAid8gWFO3nnwC1P4+EHf49lfEWNPJ1zk8uw2mKhWA8xWsZmJeuRwdCJxaFsvaM
wu/pzo6p6WgKnzwd7qv3nRwaI/gyTARYjoI1Ec3Xrh+/HbWLay86u8QXPUBSaRzr
/fAdJ6UpR/OLRKnPsZd2YIV20M0qDUh0N9Rp31eQpiTey4V5u21jSrzESHGGpdBP
0AhrCJT+g5l6lAeDXV5Bx/OqVreKrGnHFcRyNwIDAQABAoIBAEaUto/xVdFj4f83
3iDD55OMHi8JdUZ0zwg/bxKHaBIV2YnV8QzW8df+cEFQGGiQKhSt0rocz1lqW2lp
K3Em7ZglCSYy+2M00HuNzHk+nhelDsU5EvyJwAcQPIMe0kymEDsh8fUR/mxdow7U
qIyzIwXmNrFPV5kd+VrhxmHxVftJZCOw29UmqWca84Xn3ksRFYOCIltHKS4to8xZ
oc/QD+P+yCtG6JwdrrsoHQ+k4D0GvASldlEnZNI45eWk25bvOo5ndcg5CLkXFg6y
Oxn5+UwvlC5XJJpj1m8yLTtUZyGiuE1CEQoUpi7Uhsuc8c7dGSH/eC9pu86kPlUy
j7626sECgYEA0xq24FpyBoJwDq84/IHIioRNJz+fBi+prkGPZQacJkHGLMdT03Ad
e6yz75EMEBz01mByHfn7rsmOca7+MHnvMkKR9BtUTy3uon1mCqHZcEzcpvZ/OzBb
PhtVd4tTYuz67SKRBs9dSETtzWC8iZE67+KyCmLeyxQfUJurVtuSKJcCgYEAwjYR
yIXKImOBvL/tn8jiCNl+RWkvAWsGIQmREsCiUWTAPIaaHBAZPwE94Kh/yDcJ15kC
6mPyiNrH4G0WR6hk25Yyhi6MPCvl9KmzZy6bRByLqUttIsFDu/vpi0iOsyOXDsrr
/ByeDRkW5mYzP7yjQVU1/JHi7x9IWb96ESpel2ECgYEAvt9QqtocbzZRC3XzGJxg
Lp4hBHPJ3VYLHbu5Y6buWMjv3lz7thmRDtnAd8O2HHuSBKN/iwsUPZ2QZcnLmxkk
5VS7kvOC73SgZ9rqAIMGjztv5qbJs0KtvSIJzOT0qGWNHAw9BONJfKaWPTRSRDBE
EdHvGOT8fLHc/o1CBm7RYFECgYAgO5MWAcZ7kKJOrlgE5fVF3LsNUBjCFn4s64aN
YzVNZvhU004ujCl0gINBjxWuKBxVEQsf1bjCJ/V2dQR2nicnHrWB+aXCstJ2mdex
r1WLp2UyktcpJoRUZcnsGP2+E5EVnm4uKJ2+tMRNNvcYg/lgdYEBS0EryLhcVOAw
iySp4QKBgBZgjSCKo9gThsOQ6EbAQ+txdlk+Jg5ig9Y4WQ52KrykTW/EPfx3zr1c
AhOwsBCr1xAyx2NhmnPUo86xYqVzs/xEdGZStk9++1Wu2vcNet2Q4zKR0Tkrmhov
rHgmv7YPN3rhR3iv14hB5vS+d/QXylDpIeaDyqsGMMiE6O+MT82j
-----END RSA PRIVATE KEY-----';
  
  /**
   * 解密密钥
   * @var string 
   */
  protected $decrypt_key = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAq/2TDuRJ3Xn04N1HiOFY
mkqA0B7C5NnvdRNziFUwhBTK6hvL9P7YjpSkWMvdRbWLtZ53T16TbdP47cIFXM4x
JQhppX5/yGx7YvNf2LwadcOQowsiVLrV4VneT+D0CnSUrIpRkE0opPEV4RVkD74Z
3MLtgDQE9f8NV9mcmTqx7YpLU/1xhJu2odXbh37h4GsF/6tNbN0L6g9gGDfB+u7d
tPeVNMLlfq8eqRrbb4hdPeXgzNlaJuO9RKXpFV6XV3lzMUt0Tu2ZbP0KQ0fagA6v
1o5+Z6oxw7O1/0VMvAqAbzVpXmmTMkmqpJf2+JZ2i2LQysybt6dwSK2XaeP1ZMov
lwIDAQAB
-----END PUBLIC KEY-----';
  
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
   * @return \YitOS\OpenSSL\RSACryptor
   */
  protected function cryptor() {
    $seperator = ($token = $this->app['request']->session()->get('client_token')) ? explode(':', $token)[1] : '';
    $cryptor = new RSACryptor($this->decrypt_key, $this->encrypt_key, $seperator);
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
      $token = $response['token'].':'.$response['seperator'].':'.$response['expires'];
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
