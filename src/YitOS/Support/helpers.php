<?php

if (!function_exists('M')) {
  /**
   * 生成模型对象
   * @author yiller <tech.yiller@yitos.cn>
   * @param string $classname
   * @return \YitOS\ModelFactory\Eloquent\Model
   */
  function M($classname) {
    return app('model.factory')->driver($classname);
  }
}