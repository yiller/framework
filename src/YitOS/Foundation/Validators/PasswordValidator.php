<?php namespace YitOS\Foundation\Validators;

/**
 * 验证密码
 *
 * @package YitOS\Foundation\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class PasswordValidator {
  
  /**
   * 验证密码
   * 由字母数字组成，并且不小于6个字符
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return (preg_match('/^[a-zA-Z0-9]+$/u', $value) && strlen($value) >= 6 && strlen($value) <= 16);
  }
  
}
