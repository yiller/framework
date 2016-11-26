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
   * @return \YitOS\ModelFactory\Drivers\Driver
   */
  public function __construct($name, $classname, $duration, $enabledSync = true) {
    // 基本配置
    $this->name = $name;
    $this->classname = $classname;
    $this->duration = intval($duration);
    $this->enabledSync = boolval($enabledSync);
    // 元素定义
    Cache::forget('elements_defined_'.$this->name);
    $elements = Cache::rememberForever('elements_defined_'.$this->name, function() {
      return $this->getElements();
    });
    if (!$elements || !is_array($elements)) {
      throw new InvalidArgumentException();
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
   * 数据同步（下行）
   * 
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
    if (!method_exists($this, 'sync_config_table')) {
      throw new InvalidArgumentException(trans('modelfactory::exception.method_not_exists', ['method' => 'sync_config_table']));
    }
    // 同步准备阶段
    $now = Carbon::now();
    $timestamp = 0;
    $config = $this->sync_config_table()->where('alias', $this->name)->first();
    if (!$config) { // 没有配置同步，第一次同步
      $config = ['name' => '', 'duration' => $this->duration];
    } elseif ($config['duration'] == 0) { // 有效周期为0，代表无须同步
      app('log')->notice('数据同步（下行）中止，无需自动同步', ['name' => $this->name]);
      //return true;
    } elseif (Carbon::createFromTimestamp($config['synchronized_at'])->addSeconds($config['duration'])->gt($now)) { // 持续时间内，无须同步
      app('log')->notice('数据同步（下行）中止，数据持续有效', ['name' => $this->name]);
      return true;
    } else {
      $timestamp = $config['synchronized_at'];
    }
    app('log')->info('数据同步（上行）开始', ['name' => $this->name]);
    // 开始同步
    $data = $this->download($timestamp);
    dd($data);
  }
  
}
