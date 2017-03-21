<?php namespace YitOS\Support\Validators;

/**
 * 验证类是否存在
 * @package YitOS\Support\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class ClassExistsValidator {
  
  /**
   * 验证类是否存在
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return class_exists($value);
  }
  
}
