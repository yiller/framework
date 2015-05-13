<?php

if (!function_exists('rules_convert')) {
  /**
   * Convert validate rules and message for laravel controller(default) or jquery validate
   * 
   * @param array $rules
   * @param string $for
   * @return \Illuminate\Support\Collection
   */
  function rules_convert($columns, $for = 'laravel') {
    $rules = $messages = [];
    foreach ($columns as $name => $items) {
      array_key_exists($name, $rules) || $rules[$name] = [];
      $for == 'laravel' || array_key_exists($name, $messages) || $messages[$name] = [];
      foreach ($items as $rule) {
        if ($for == 'laravel') {
          $rules[$name][] = array_key_exists('value', $rule) ? $rule['name'].':'.$rule['value'] : $rule['name'];
          array_key_exists('message', $rule) && $messages[$name.'.'.$rule['name']] = $rule['message'];
        } else {
          $rules[$name][$rule['name']] = array_key_exists('value', $rule) ? $rule['value'] : true;
          array_key_exists('message', $rule) && $messages[$name][$rule['name']] = $rule['message'];
        }
      }
      if (empty($rules[$name])) unset($rules[$name]);
    }
    return collect(['rules' => $rules, 'messages' => $messages]);
  }
}