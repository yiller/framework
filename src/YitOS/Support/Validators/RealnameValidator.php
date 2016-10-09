<?php namespace YitOS\Support\Validators;

/**
 * 验证真实姓名
 * 
 * @package YitOS\Support\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class RealnameValidator {
  
  /**
   * 验证真实姓名
   * 由2-8位中文字符组成
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $value) && mb_strlen($value) >= 2 && mb_strlen($value) <= 8;
  }
  
}
