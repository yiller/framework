<?php

if (!function_exists('M')) {
  /**
   * 生成模型对象
   * @author yiller <tech.yiller@yitos.cn>
   * @param string $classname
   * @return \YitOS\ModelFactory\Factories\Factory
   */
  function M($classname) {
    return app('model.factory')->driver($classname);
  }
}

if (!function_exists('array_only_by_sort')) {
  /**
   * 根据键名按顺序提取数组中的元素
   * @author yiller <tech.yiller@yitos.cn>
   * @param array $array
   * @param array $index
   * @return array
   */
  function array_only_by_sort($array, $index) {
    $result = [];
    foreach ($index as $key) {
      foreach ($array as $k => $v) {
        if ($key == $k) {
          $result[$k] = $v;
          break;
        }
      }
    }
    return $result;
  }
}

if (!function_exists('price_format')) {
  /**
   * 获得价格格式
   * 
   * @author yiller <tech.yiller@yitos.cn>
   * @param double $price
   * @return string
   */
  function price_format($price) {
    return number_format($price, 2, '.', ',');
  }
}

