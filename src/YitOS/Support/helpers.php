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