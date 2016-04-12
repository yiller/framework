<?php namespace YitOS\Foundation\Validators;

/**
 * 验证账号
 *
 * @package YitOS\Foundation\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class AccountValidator {
  
  /**
   * 验证账号
   * Email、手机号码或者用户名
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false || 
           (preg_match('/^[\pL\pM\pN_]+$/u', $value) && mb_strlen($value) >= 2 && mb_strlen($value) <= 8) || 
           (preg_match('/^1\d{10}$/u', $value));
  }
  
}
