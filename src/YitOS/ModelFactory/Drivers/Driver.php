<?php namespace YitOS\ModelFactory\Drivers;

use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Support\Facades\Cache;

/**
 * 定义数据库工厂接口
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Drivers
 * @abstract
 */
abstract class Driver {
  
  const LOG_LEVEL_DEBUG     = 'debug';
  const LOG_LEVEL_INFO      = 'info';
  const LOG_LEVEL_WARNING   = 'warning';
  const LOG_LEVEL_ERROR     = 'error';
  const LOG_LEVEL_EMERGENCY = 'emergency';
  
  /**
   * 对应的实体名字
   * @var string 
   */
  protected $name = '';
  
  /**
   * 同步数据的生存周期
   * @var integer
   */
  protected $duration = 0;
  
  /**
   * 数据模型对象类名
   * @var string
   */
  protected $classname = null;
  
  /**
   * 是否需要同步上传
   * @var bool 
   */
  protected $enabledSync = true;
  
  /**
   * 实体结构
   * @var array
   */
  protected $elements = [];
  
  /**
   * 初始化工厂
   * @access public
   * @param string $name
   * @param string $classname
   * @param integer $duration
   * @param bool $enabledSync
   * @return \YitOS\ModelFactory\Drivers\Driver
   * 
   * @throws \InvalidArgumentException
   */
  public function __construct($name, $classname, $duration, $enabledSync = true) {
    // 基本配置
    $this->name = $name;
    $this->classname = $classname;
    $this->duration = intval($duration);
    $this->enabledSync = boolval($enabledSync);
    // 元素定义
    //Cache::forget('elements_defined_'.$this->name);
    $elements = Cache::rememberForever('elements_defined_'.$this->name, function() {
      return $this->getElements();
    });
    if (!$elements || !is_array($elements)) {
      throw new InvalidArgumentException;
    }
    $this->elements = $elements;
    
    return $this;
  }
  
  /**
   * 元素定义
   * @abstract
   * @access protected
   * @return array
   */
  abstract protected function getElements();
  
  /**
   * 获得SQLBuilder
   * @abstract
   * @access public
   * @return \Illuminate\Database\Eloquent\Builder
   */
  abstract public function builder();
  
  /**
   * 获得模型实例
   * @access public
   * @return mixed
   */
  public function instance() {
    $model_class = $this->classname;
    $instance = new $model_class;
    return $instance;
  }
  
  /**
   * 获得元素配置
   * @access public
   * @param string $name
   * @return mixed
   */
  public function elements($name = '') {
    if (!$name) {
      return $this->elements;
    }
    foreach ($this->elements as $element) {
      if ($element['alias'] == $name) {
        return $element;
      }
    }
    return null;
  }
  
  /**
   * 获得实体名字
   * @accss public
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * 储存数据
   * @access public
   * @param  array $data
   * @return mixed
   */
  public function save($data) {
    if (!isset($data['__']) || !($instance = $this->builder()->find($data['__']))) {
      $instance = $this->instance();
    }
    unset($data['__'], $data['id']);
    $instance->fill($data);
    if ($this->enabledSync) {
      return $this->syncUpload($instance) ?: null;
    } else {
      return $instance->save() ? $instance : null;
    }
  }
  
  /**
   * 数据同步（上行）
   * @access protected
   * @param mixed $instance
   * @return mixed
   */
  protected function syncUpload($instance) {
    if (!$this->enabledSync) {
      return $instance->save() ? $instance : null;
    }
    if (!method_exists($this, 'upload')) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => 'upload']));
    }
    // 同步准备阶段
    $now = Carbon::now();
    $attributes = $instance->getAttributes();
    $data = [];
    foreach ($this->elements as $element) {
      $data[$element['alias']] = isset($attributes[$element['alias']]) ? $attributes[$element['alias']] : '';
    }
    $data['id'] = isset($attributes['id']) ? intval($attributes['id']) : 0;
    $data['parent_id'] = isset($attributes['parent_id']) && ($parent = $this->builder()->find($attributes['parent_id'])) ? intval($parent->id) : 0;
    $data['sort_order'] = isset($attributes['sort_order']) ? intval($attributes['sort_order']) : 0;
    method_exists($this->instance(), 'uploading') && $data = $this->instance()->uploading($data);
    app('log')->info('数据同步（上行）开始', ['name' => $this->name]);
    // 开始同步
    $respond = $this->upload($data);
    if (!$respond) {
      app('log')->emergency('数据同步（上行）失败', ['name' => $this->name]);
      return false;
    }
    extract($respond);
    $model = $this->builder()->updateOrCreate(['id' => $data['id']], $data);
    // 上行同步之后
    $model && method_exists($this, 'synchronized') && $model = $this->synchronized($model);
    $model && method_exists($model, 'synchronized') && $model = $model->synchronized();
    if (!$model) {
      app('log')->emergency('数据同步（上行）失败，基库编号：#'.$data['id'], ['name' => $this->name]);
      return false;
    }
    $related = isset($related) ? $related : [];
    foreach ($related as $alias => $recs) {
      foreach ($recs as $k => $rec) {
        $instance = M($alias)->where('id', $k)->first();
        $instance && $instance->update($rec);
      }
    }
    app('log')->info('数据同步（上行）成功，基库编号：# '.$model->id, ['name' => $this->name]);
    return true;
  }
  
  /**
   * 数据同步（下行）
   * @access public
   * @return bool
   */
  public function syncDownload() {
    if (!$this->enabledSync) {
      return true;
    }
    if (!method_exists($this, 'download')) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => 'download']));
    }
    if (!method_exists($this, '_sync')) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => '_sync']));
    }
    // 同步准备阶段
    $now = Carbon::now();
    $timestamp = 0;
    $config = $this->_sync()->where('alias', $this->name)->first();
    if (!$config) { // 没有配置同步，第一次同步
      $config = ['name' => '', 'duration' => $this->duration];
    } elseif ($config['duration'] == 0) { // 有效周期为0，代表无须同步
      app('log')->notice('数据同步（下行）中止，无需自动同步', ['name' => $this->name]);
      return true;
    } elseif (Carbon::createFromTimestamp($config['synchronized_at'])->addSeconds($config['duration'])->gt($now)) { // 持续时间内，无须同步
      app('log')->notice('数据同步（下行）中止，数据持续有效', ['name' => $this->name]);
      return true;
    } else {
      $timestamp = $config['synchronized_at'];
    }
    app('log')->info('数据同步（下行）开始', ['name' => $this->name]);
    // 开始同步
    $data = $this->download($timestamp);
    $models = [];
    foreach ($data as $item) {
      $models[] = $this->builder()->updateOrCreate(['id' => $item['id']], $item);
    }
    // 同步之后执行
    $objects = [];
    foreach ($models as $model) {
      $model && method_exists($this, 'synchronized') && $model = $this->synchronized($model);
      $model && method_exists($model, 'synchronized') && $model = $model->synchronized();
      $model && $objects[] = $model;
    }
    // 更新配置记录
    $config['synchronized_at'] = $now->format('U');
    $this->_sync()->updateOrInsert(['alias' => $this->name], $config);
    if ($objects) {
      app('log')->info('数据同步（下行）成功，成功同步 '.count($objects).' 条记录', ['name' => $this->name]);
      return true;
    } else {
      app('log')->emergency('数据同步（下行）失败', ['name' => $this->name]);
      return false;
    }
  }
  
}
