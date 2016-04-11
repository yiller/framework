<?php namespace YitOS\OpenSSL;

/**
 * RSA加密器
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\OpenSSL
 */
class RSACryptor {
  
  /**
   * 公钥
   * 
   * @var string
   */
  protected $public_key = '';
  
  /**
   * 私钥
   * 
   * @var string
   */
  protected $private_key = '';
  
  /**
   * 密钥长度
   * 
   * @var integer
   */
  protected $size = 256;
  
  /**
   * 分割符号键名
   * 
   * @var string
   */
  protected $seperator = '';
  
  /**
   * 生成RSA加密器实例
   * 
   * @access public
   * @param string $public_key
   * @param string $private_key
   * @param string $seperator
   * @param integer $size
   */
  public function __construct($public_key, $private_key, $seperator = '', $size = 256) {
    $this->public_key = $public_key;
    $this->private_key = $private_key;
    $this->seperator = $seperator;
    $this->size = $size;
  }
  
  /**
   * RSA加密
   * 
   * @access public
   * @param string $plain
   * @return string
   */
  public function encrypt($plain) {
    $encrypted = '';
    $key = openssl_pkey_get_private($this->private_key);
    if ($this->seperator) {
      $arr = str_split($plain, $this->size - 11);
      foreach ($arr as $k => $v) {
        $temp = '';
        openssl_private_encrypt($v, $temp, $key);
        $arr[$k] = base64_encode($temp);
      }
      $encrypted = implode($this->seperator, $arr);
    } else {
      openssl_private_encrypt($plain, $encrypted, $key);
      $encrypted = base64_encode($encrypted);
    }
    return $encrypted;
  }
  
  /**
   * RSA解密
   * 
   * @access public
   * @param string $encrypted
   * @return string
   */
  public function decrypt($encrypted) {
    $plain = '';
    $key = openssl_pkey_get_public($this->public_key);
    if ($this->seperator && (false !== strpos($encrypted, $this->seperator))) {
      $arr = explode($this->seperator, $encrypted);
      foreach ($arr as $k => $v) {
        $temp = '';
        openssl_public_decrypt(base64_decode($v), $temp, $key);
        $arr[$k] = $temp;
      }
      $plain = implode('', $arr);
    } else {
      openssl_public_decrypt(base64_decode($encrypted), $plain, $key);
    }
    return $plain;
  }
  
}
