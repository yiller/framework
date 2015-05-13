<?php

if (!function_exists('rules_convert')) {
  /**
   * Convert validate rules and message for laravel controller(default) or jquery validate
   * 
   * @param array $rules
   * @param string $for
   * @return \Illuminate\Support\Collection
   */
  function rules_convert($rules, $for = 'laravel') {
    return collect($rules);
  }
}