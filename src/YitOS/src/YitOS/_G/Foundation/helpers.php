<?php

if (!function_exists('YitOS_RulesConvert')) {
  /**
   * 转换 Column Meta 为Laravel或JQuery Validate识别的验证规则
   * 
   * @param array $rules
   * @param string $for
   * @return \Illuminate\Support\Collection
   */
  function YitOS_RulesConvert($columns, $for = 'laravel') {
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

if (!function_exists('YitOS_route')) {
  /**
   * 根据路由生成URL
   * 
   * @param string $route
   * @param array $params
   * @return  string
   */
  function YitOS_route($route, $params = []) {
    $temp = explode('.', $route);
    if ($temp[0] == 'yitos' && $temp[1] == 'backend' && app('auth')->check() && app('request')->user()->enterprise) {
      $params['company'] = app('request')->user()->enterprise->slug;
    }
    return route($route, $params);
  }
  
}

if (!function_exists('YitOS_action')) {
  /**
   * 根据执行控制器生成URL
   * 
   * @param string $action
   * @param array $params
   * @return string
   */
  function YitOS_action($action, $params = []) {
    if ('\\YitOS\Backend' == substr($action, 0, strlen('\\YitOS\Backend')) && app('auth')->check() && app('request')->user()->enterprise) {
      $params['company'] = app('request')->user()->enterprise->slug;
    }
    return action($action, $params);
  }
}
