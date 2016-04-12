<?php namespace YitOS\Foundation\Validators;

/**
 * 验证身份证号码
 *
 * @package YitOS\Foundation\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class IdCardValidator {
  
  /**
   * 验证身份证号码
   * 由18位数字组成，第一位数字不能是0，最后一个数字可以是X
   * 
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return preg_match('/^[1-9][0-9]{16}[0-9X]$/u', $value);
  }
  
}
