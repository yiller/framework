<?php namespace YitOS\_G\Validation;

use Illuminate\Validation\Validator as BaseValidator;

class Validator extends BaseValidator {
  
  /**
   * 验证账号
   * Email、手机号码或者用户名
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  protected function validateAccount($attribute, $value, $parameters) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false || 
           (preg_match('/^[\pL\pM\pN_]+$/u', $value) && mb_strlen($value) >= 6) || 
           (preg_match('/^1\d{10}$/u', $value));
  }
  
  /**
   * 验证用户名
   * 由字母数字下划线组成，并且不小于6个字符
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  protected function validateUsername($attribute, $value, $parameters) {
    return preg_match('/^[\pL\pM\pN_]+$/u', $value) && mb_strlen($value) >= 6;
  }
  
  /**
   * 验证手机号码
   * 由1开头的11位数字组成
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  protected function validatePhoneCN($attribute, $value, $parameters) {
    return preg_match('/^1\d{10}$/u', $value);
  }
  
}
