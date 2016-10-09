<?php namespace YitOS\Support\Validators;

/**
 * 验证用户名
 * 
 * @package YitOS\Support\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class UsernameValidator {
  
  /**
   * 验证用户名
   * 由字母数字下划线组成，并且不小于6个字符
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return preg_match('/^[\pL\pM\pN_]+$/u', $value) && mb_strlen($value) >= 6 && mb_strlen($value) <= 16;
  }
  
}
