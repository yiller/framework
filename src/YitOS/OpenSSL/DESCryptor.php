<?php namespace YitOS\OpenSSL;

/**
 * DES加密器
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\OpenSSL
 */
class DESCryptor {
  
  /**
   * 密钥
   * @var string
   */
  protected $secret_key = 'YitOS_OPENSSL_DESCRYPTOR';
  
  /**
   * 生成DES加密器实例
   * @access public
   * @param string $secret_key
   */
  public function __construct($secret_key = '') {
    $this->secret_key = $secret_key ?: 'YitOS_OPENSSL_DESCRYPTOR';
  }
  
  /**
   * DES加密
   * @access public
   * @param string $plain
   * @return string
   */
  public function encrypt($plain) {
    $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $pad = $size - (strlen($plain) % $size);
    $plain .= str_repeat(chr($pad), $pad);
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    mcrypt_generic_init($td, $this->secret_key, $iv);
    $encrypted = mcrypt_generic($td, $plain);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return base64_encode($encrypted);
  }
  
  /**
   * DES解密
   * @access public
   * @param string $encrypted
   * @return string
   */
  public function decrypt($encrypted) {
    $plain = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->secret_key, base64_decode($encrypted), MCRYPT_MODE_ECB);
    $length = strlen($plain);
    $pad = ord($plain[$length-1]);
    return substr($plain, 0, -$pad);
  }
  
}
