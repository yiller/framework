<?php namespace YitOS\Support\Validators;

/**
 * 验证手机号码
 *
 * @package YitOS\Support\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class PhoneValidator {
  
  /**
   * 验证手机号码
   * 由1开头的11位数字组成
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return preg_match('/^1\d{10}$/u', $value);
  }
  
}
