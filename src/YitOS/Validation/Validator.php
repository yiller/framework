<?php namespace YitOS\Validation;

use Illuminate\Validation\Validator as BaseValidator;

class Validator extends BaseValidator {
  
  protected function validateAccount($attribute, $value, $parameters) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false || 
           (preg_match('/^[\pL\pM\pN_]+$/u', $value) && mb_strlen($value) >= 6) || 
           (preg_match('/^1\d{10}$/u', $value));
  }
  
  protected function validateUsername($attribute, $value, $parameters) {
    return preg_match('/^[\pL\pM\pN_]+$/u', $value) && mb_strlen($value) >= 6;
  }
  
  protected function validatePhoneCN($attribute, $value, $parameters) {
    return preg_match('/^1\d{10}$/u', $value);
  }
  
}
