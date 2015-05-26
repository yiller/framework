<?php namespace YitOS\_G\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;

class DefModel extends BaseModel {

	protected $table = 'def_models';
  
  protected $fillable = ['name', 'label', 'class', 'user_id'];
  
  public function columns() {
    return $this->hasMany('YitOS\Database\Eloquent\DefModelColumn', 'model_id');
  }
  
  /**
   * 通过名称获得模型的定义对象
   * 
   * @access  public
   * @param   string $name        模型名称
   * @param   boolean $exception  是否抛出异常
   * @return  YitOS\_G\Database\Eloquent\DefModel
   */
  public static function getModelByName($name, $exception = true) {
    if ($exception) {
      return static::where('name', $name)->firstOrFail();
    } else {
      return static::where('name', $name)->first();
    }
  }

}
