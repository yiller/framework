<?php namespace YitOS\_G\Database\Eloquent;

use Schema;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel {
  
  protected $model = null;
  
  public function __construct(array $attributes = array()) {
    $this->initialize();
    
    parent::__construct($attributes);
  }
  
  /**
   * 根据模型的定义对象自动填充fillable成员
   * 
   * @access protected
   * @return void
   */
  protected function initialize() {
    if (!$this->model) {
      $this->model = str_singular($this->getTable());
    }
    $this->model = DefModel::getModelByName($this->model);
    foreach ($this->model->columns as $column) 
      if (!in_array($column->name, $this->fillable)) 
        $this->fillable[] = $column->name;
  }
  
  /**
   * 获得模型的定义对象
   * 
   * @return YitOS\_G\Database\Eloquent\DefModel
   */
  protected function getModel() {
    return $this->model;
  }
  
  /**
   * 重写成员变量的获取方法
   * 当默认方式无法获得成员变量时，通过定义对象的列信息自动生成关联方法
   * 
   * @param string $key   成员变量名称
   * @return mixed
   */
  public function getAttribute($key) {
    $inAttributes = array_key_exists($key, $this->attributes);
    if ($inAttributes || $this->hasGetMutator($key)) return $this->getAttributeValue($key);
    if (array_key_exists($key, $this->relations)) return $this->relations[$key];
    try { return $this->getRelationshipFromMethod($key); }
    catch(BadMethodCallException $e) {}
  }
  
  /**
   * 重写成员方法的调用
   * 当默认方式无法调用成员方法时，通过定义对象的列信息自动生成关联方法
   * 
   * @param string $method    成员方法名称
   * @param array $parameters 调用参数
   * @return mixed
   */
  public function __call($method, $parameters)
	{
		if (in_array($method, array('increment', 'decrement')))
		{
			return call_user_func_array(array($this, $method), $parameters);
		}
    
    $name = $method;
    $defModel = DefModel::getModelByName(str_singular($name), false);
    if ($defModel && $name == str_plural($name)) { // 复数
      $related = $defModel->class;
      $instance = new $related;
      $pivot = $this->joiningTable($instance);
      if (Schema::hasTable($pivot)) { // 枢纽表存在 代表m:n
        return $this->belongsToMany($defModel->class, $pivot, $this->getForeignKey(), $instance->getForeignKey());
      }
      if (Schema::hasColumn($instance->getTable(), $this->getForeignKey())) { // 关联表存在外键 代表1:m
        return $this->hasMany($defModel->class, $this->getForeignKey());
      }
    }
    if ($defModel && $name == str_singular($name)) { // 单数
      $related = $defModel->class;
      $instance = new $related;
      if (Schema::hasColumn($this->table, $instance->getForeignKey())) { // 当前表含有关联表外键 belongsTo
        return $this->belongsTo($defModel->class, $instance->getForeignKey());
      }
      if (Schema::hasColumn($instance->getTable(), $this->getForeignKey())) { // 关联表含有当前表外键 hasOne
        return $this->hasOne($defModel->class, $this->getForeignKey());
      }
    }

		$query = $this->newQuery();

		return call_user_func_array(array($query, $method), $parameters);
	}
  
  /**
   * 重写枢纽表生成方式为 
   * 当前模型名称复数（表名）_关联模型名称复数（表名）
   * 
   * @param YitOS\_G\Database\Eloquent\Model $related 关联的模型对象
   * @return string
   */
  public function joiningTable($related) {
    $base = snake_case(str_plural($this->model->name));
    $related = snake_case(str_plural($related->model->name));
    $models = array($related, $base);
    sort($models);
    return strtolower(implode('_', $models));
  }
  
  /**
   * 重写外键的生成方式为
   * 当前模型名称_id
   * 
   * @return string
   */
  public function getForeignKey() {
		return snake_case($this->model->name).'_id';
	}
  
}
