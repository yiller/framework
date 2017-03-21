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
  protected $enabledSync = false;
  
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
   * @return \YitOS\ModelFactory\Drivers\Driver
   * 
   * @throws \InvalidArgumentException
   */
  public function __construct($name, $classname) {
    // 基本配置
    $this->name = $name;
    $this->classname = $classname;
    
    $table = static::metaTable();
    if (!$table) return;
    
    $meta = $table->where('alias', $name)->first();
    if (!$meta) {
      $model = new $classname;
      extract($this->getMetaBySocket());
      $meta = [
        'name'     => $entity['name'],
        'alias'    => $entity['alias'],
        'model'    => $classname,
        'built_in' => !$entity['account_id'],
        'elements' => $elements,
        'duration' => intval($model->duration),
        'synchronized_at' => 0
      ];
      $table->insert($meta);
    }
    
    $this->elements = $meta['elements'];
    $this->duration = intval($meta['duration']);
    $this->enabledSync = $this->duration > 0;
    
    return $this;
  }
  
  /**
   * 获得同步配置表
   * @static
   * @access public
   * @return type
   */
  public static function metaTable() {
    return null;
  }
  
  /**
   * 初始化元素定义
   * @abstract
   * @access protected
   * @return array
   */
  abstract protected function getMetaBySocket();
  
  /**
   * 获得SQLBuilder
   * @abstract
   * @access public
   * @return \Illuminate\Database\Eloquent\Builder
   */
  abstract public function builder();
  
  /**
   * 获得实体名字
   * @accss public
   * @return string
   */
  public function name() {
    return $this->name;
  }
  
  /**
   * 获得所有元素配置
   * @access public
   * @param string $name
   * @return mixed
   */
  public function elements($name = '') {
    return $this->elements;
  }
  
  /**
   * 获得指定元素配置
   * 
   * @access public
   * @param string $name
   * @return mixed
   */
  public function element($name) {
    return array_key_exists($name, $this->elements) ? $this->elements[$name] : null;
  }
  
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
   * 储存多行数据
   * @access public
   * @param array $multidata
   * @return array
   */
  public function saveMany($multidata) {
    $instances = [];
    foreach ($multidata as $data) {
      if (!isset($data['__']) || empty($data['__']) || !($instance = $this->builder()->find($data['__']))) {
        $instance = $this->instance();
      }
      unset($data['__'], $data['id']);
      $instance->fill($data);
      !$this->enabledSync && $instance->save();
      $instances[] = $instance;
    }
    $this->enabledSync && $instances = $this->syncUpload($instances);
    return $instances;
  }
  
  /**
   * 储存数据
   * @access public
   * @param  array $data
   * @return mixed
   */
  public function save($data) {
    $instances = $this->saveMany([$data]);
    return $instances ? $instances[0] : null;
  }
  
  /**
   * 数据同步（上行）
   * @access protected
   * @param array $instances
   * @return array
   */
  protected function syncUpload($instances) {
    if (!$this->enabledSync) {
      return [];
    }
    if (!method_exists($this, 'upload')) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => 'upload']));
    }
    // 同步准备阶段
    $now = Carbon::now();
    $data = [];
    foreach ($instances as $instance) {
      $real_attributes = $instance->getAttributes();
      $attributes = method_exists($instance, 'uploading') ? $instance->uploading() : $real_attributes;
      $item = [];
      foreach ($this->elements as $element) {
        $item[$element['alias']] = isset($attributes[$element['alias']]) ? $attributes[$element['alias']] : '';
      }
      // 不能操纵原库ID、PARENT_ID以及SORT_ORDER要素
      $item['id'] = isset($real_attributes['id']) ? intval($real_attributes['id']) : 0;
      $item['parent_id'] = isset($real_attributes['parent_id']) && ($parent = $this->builder()->find($real_attributes['parent_id'])) ? intval($parent->id) : 0;
      $item['sort_order'] = isset($real_attributes['sort_order']) ? intval($real_attributes['sort_order']) : 0;
      $data[] = $item;
    }
    app('log')->info('数据同步（上行）开始', ['name' => $this->name]);
    // 开始同步
    $respond = $this->upload($data);
    if (!$respond) {
      app('log')->emergency('数据同步（上行）失败', ['name' => $this->name]);
      return null;
    }
    extract($respond);
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
    if (!$objects) {
      app('log')->emergency('数据同步（上行）失败', ['name' => $this->name]);
      return null;
    }
    $related = isset($related) ? $related : [];
    foreach ($related as $alias => $recs) {
      foreach ($recs as $k => $rec) {
        $instance = M($alias)->builder()->where('id', $k)->first();
        $instance && $instance->update($rec);
      }
    }
    app('log')->info('数据同步（上行）成功，成功同步 '.count($objects).' 条记录', ['name' => $this->name]);
    return $objects;
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
    
    $table = static::metaTable();
    if (!method_exists($this, 'download')) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => 'download']));
    }
    if (!$table) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => 'metaTable']));
    }
    // 同步准备阶段
    $now = Carbon::now();
    $meta = $table->where('alias', $this->name)->first();
    if (Carbon::createFromTimestamp($meta['synchronized_at'])->addSeconds($meta['duration'])->gt($now)) { // 持续时间内，无须同步
      app('log')->notice('数据同步（下行）中止，数据持续有效', ['name' => $this->name]);
      return true;
    }
    $timestamp = $meta['synchronized_at'];
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
    $table->updateOrInsert(['alias' => $this->name], ['synchronized_at' => $now->format('U')]);
    if ($objects) {
      app('log')->info('数据同步（下行）成功，成功同步 '.count($objects).' 条记录', ['name' => $this->name]);
      return true;
    } else {
      app('log')->emergency('数据同步（下行）失败或成功同步 0 条记录', ['name' => $this->name]);
      return false;
    }
  }
  
}
