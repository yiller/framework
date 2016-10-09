<?php namespace YitOS\Support\Validators;

/**
 * 验证地址slug
 *
 * @package YitOS\Support\Validators
 * @author yiller <tech.yiller@yitos.cn>
 */
class SlugValidator {
  
  /**
   * 验证地址slug
   * 由字母数字下划线或中横线组成，并且不超过20个字符
   * @param string $attribute   属性名称
   * @param string $value       属性值
   * @param array $parameters   额外参数
   * @return boolean
   */
  public function validate($attribute, $value, $parameters) {
    return preg_match('/^[A-Za-z0-9_\-]+$/u', $value) && strlen($value) <= 20;
  }
  
}
