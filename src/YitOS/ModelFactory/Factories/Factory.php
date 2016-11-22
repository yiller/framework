<?php namespace YitOS\ModelFactory\Factories;

use BadMethodCallException;
use InvalidArgumentException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use YitOS\Support\Facades\WebSocket;

/**
 * 定义数据库工厂接口
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Factories
 */
abstract class Factory {
  
  const LOG_LEVEL_DEBUG     = 'debug';
  const LOG_LEVEL_INFO      = 'info';
  const LOG_LEVEL_WARNING   = 'warning';
  const LOG_LEVEL_ERROR     = 'error';
  const LOG_LEVEL_EMERGENCY = 'emergency';
  
  /**
   * 对应的实体别名
   * @var string 
   */
  protected $entity = '';
  
  /**
   * 同步数据的生存周期
   * @var integer
   */
  protected $duration = 0;
  
  /**
   * 数据模型对象
   * @var \Illuminate\Database\Eloquent\Model
   */
  protected $model = null;
  
  /**
   * 是否需要同步上传
   * @var bool 
   */
  protected $needSyncUpload = true;
  
  /**
   * 实体结构
   * @var array
   */
  protected $elements = [];
  
  /**
   * 初始化工厂
   * @access public
   * @param string $entity
   * @param integer $duration
   * @param string $classname
   * @return \YitOS\ModelFactory\Factories\Factory
   */
  public function __construct($entity, $duration, $classname) {
    if (!class_exists($classname)) {
      throw new InvalidArgumentException();
    }
    $this->model = new $classname();
    $this->entity = trim($entity);
    $this->duration = intval($duration);
    return $this;
  }
  
  /**
   * 获得实体别名
   * @accss public
   * @return string
   */
  public function entity() {
    return $this->entity;
  }
  
  /**
   * 获得数据模型
   * @access public
   * @return mixed
   */
  public function model() {
    if (!$this->model) {
      return null;
    }
    $classname = get_class($this->model);
    return new $classname();
  }
  
  /**
   * 是否需要同步上传
   * @accss public
   * @param bool $need
   * @return \YitOS\MModelFactory\Factories\Factory
   */
  public function needSyncUpload($need = true) {
    $this->needSyncUpload = $need;
    return $this;
  }
  
  /**
   * 元素定义
   * @access public
   * @param string $name
   * @return array
   */
  public function elements($name = '') {
    if (!$this->elements) {
      //Cache::forget('elements_defined_'.$this->entity);
      $elements = Cache::rememberForever('elements_defined_'.$this->entity, function() {
        $response = WebSocket::sync_elements(['entity' => $this->entity]);
        return $response && $response['code'] == 1 ? $response['elements'] : [];
      });
      $this->elements = $elements;
    }
    if (!$name) {
      return $this->elements;
    }
    foreach ($this->elements as $element) {
      if ($element['alias'] == $name) {
        return $element;
      }
    }
    return [];
  }
  
  /**
   * 插入同步日志
   * @access protected
   * @param string $level
   * @param string $message
   * @param integer $timestamp
   * @return bool
   */
  protected function logs($level, $message, $timestamp = 0) {
    $logger = $this->tableLogs();
    if (!$logger) {
      return false;
    }
    $timestamp = $timestamp ?: Carbon::now()->format('U');
    $log = [
      'entity' => $this->entity,
      'level' => $level,
      'message' => $message,
      'timestamp' => $timestamp
    ];
    if (app('auth')->user()) {
      $log['user_id'] = app('auth')->user()->getAuthIdentifier();
    } else {
      $log['user_id'] = '';
    }
    return $this->tableLogs()->insert($log);
  }
  
  /**
   * 储存数据
   * @access public
   * @param  array $data
   * @return \Illuminate\Database\Eloquent\Model
   */
  public function save($data) {
    if (isset($data['__']) && $model = $this->model->find($data['__'])) {
      unset($data['__']);
      $instance = $model->fill($data);
    } else {
      unset($data['__']);
      $instance = $this->model()->fill($data);
    }
    if ($this->needSyncUpload) {
      $this->model = $instance;
      return $this->syncUpload() ? $this->model : null;
    } else {
      return $instance->save() ? $instance : null;
    }
  }
  
  /**
   * 数据同步（上行）
   * @access public
   * @return bool
   */
  public function syncUpload() {
    if (!$this->entity || !$this->tableSync()) {
      Log::notice('数据同步（上行）中止，未定义实体类型', ['classname' => get_class($this)]);
      return true;
    }
    
    $now = Carbon::now();
    $entity = $this->entity;
    $data = $this->model->getAttributes();
    
    $data['id'] = isset($data['_id']) && ($model = $this->model->find($data['_id'])) ? intval($model->id) : 0;
    $data['parent_id'] = isset($data['parent_id']) && ($parent = $this->model->find($data['parent_id'])) ? intval($parent->id) : 0;
    $data['sort_order'] = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
    unset($data['_id'], $data['_token'], $data['method']);
    
    if (method_exists($this->model, 'syncUploading') && !($data = $this->model->syncUploading($data))) {
      return false;
    }
    
    $this->logs(static::LOG_LEVEL_INFO, '数据同步（上行）开始', $now->format('U'));
    $response = WebSocket::sync_upload(compact('entity', 'data'));
    if ($response && $response['code'] == 1) {
      $message = '数据同步（上行）成功，基库编号： #'.$response['data']['id'];
      $data = $response['data'];
      $provider = app('auth')->getProvider();
      $account_id = $data['account_id'];
      unset($data['account_id']);
      $user = $provider->retrieveByCredentials(['id' => $account_id]);
      if ($user) {
        $data['user_id'] = $user->getAuthIdentifier();
        $data['user'] = array_only($user->toArray(), ['account_username', 'realname', 'mobile']);
        $data['user']['team'] = array_only($user->team, ['name', 'alias']);
      } else {
        $data['user_id'] = '';
        $data['user'] = [];
      }
      $parent = $this->model->where('id', $data['parent_id'])->first();
      if ($parent) {
        $data['parent_id'] = $parent->getKey();
        $data['parent'] = array_only($parent->toArray(), ['label', 'link', 'alias']);
      } else {
        $data['parent_id'] = '';
        $data['parent'] = [];
      }
      $data['parents'] = $data['parents'] ? $this->model->whereIn('id', $data['parents'])->pluck($this->getKeyName())->toArray() : [];
      $data['children'] = $data['children'] ? $this->model->whereIn('id', $data['children'])->pluck($this->getKeyName())->toArray() : [];
      
      if (method_exists($this->model, 'syncUploaded')) {
        $data = $this->model->syncUploaded($data);
      }
      
      $model = $this->model->where('id', $data['id'])->first();
      if ($model) {
        $this->model = $model;
      }
      
      if (!$this->model->fill($data)->save()) {
        $this->logs(static::LOG_LEVEL_EMERGENCY, '数据同步（上行）失败，失败原因：数据保存出错');
      }
      $related = isset($response['related']) ? $response['related'] : [];
      foreach ($related as $alias => $recs) {
        foreach ($recs as $k => $rec) {
          $instance = M($alias)->where('id', $k)->first();
          $instance && $instance->update($rec);
        }
      }
      return $this->logs(static::LOG_LEVEL_INFO, $message);
    } else {
      $this->logs(static::LOG_LEVEL_EMERGENCY, '数据同步（上行）失败，失败原因：'.($response ? $response['message'] : '未知'));
      return false;
    }
    return false;
  }
  
  /**
   * 数据同步（下行）
   * 
   * @access public
   * @return bool
   */
  public function syncDownload() {
    $timestamp = 0;
    if (!$this->entity || !$this->tableSync()) {
      Log::notice('数据同步（下行）中止，未定义实体类型', ['classname' => get_class($this)]);
      return true;
    }

    $now = Carbon::now();
    $params = ['entity' => $this->entity];
    $rec = $this->tableSync()->where('alias', $this->entity)->first();
    if (!$rec) { // 没有配置同步，第一次同步
      $timestamp = 0;
      $duration = $this->duration;
      $rec = ['name' => '', 'duration' => $duration];
    } elseif ($rec['duration'] == 0) { // 有效周期为0，代表无须同步
      Log::notice('数据同步（下行）中止，无需自动同步', ['entity' => $this->entity]);
      return true;
    } elseif (Carbon::createFromTimestamp($rec['synchronized_at'])->addSeconds($rec['duration'])->gt($now)) { // 持续时间内，无须同步
      Log::notice('数据同步（下行）中止，数据持续有效', ['entity' => $this->entity]);
      return true;
    } else {
      $timestamp = $rec['synchronized_at'];
    }
    if ($timestamp > 0) {
      $params['last_synchronized_at'] = $timestamp;
    }
    
    $this->logs(static::LOG_LEVEL_INFO, '数据同步（下行）开始', $now->format('U'));
    
    $response = WebSocket::sync_download($params);
    if ($response && $response['code'] == 1) {
      $message = '数据同步（下行）成功，成功同步 '.$response['total'].' 条记录';
      $objects = [];
      $provider = app('auth')->getProvider();
      foreach ($response['data'] as $data) {
        $account_id = $data['account_id'];
        unset($data['account_id']);
        $user = $provider->retrieveByCredentials(['id' => $account_id]);
        if ($user) {
          $data['user_id'] = $user->getAuthIdentifier();
          $data['user'] = array_only($user->toArray(), ['account_username', 'realname', 'mobile']);
          $data['user']['team'] = array_only($user->team, ['name', 'alias']);
        } else {
          $data['user_id'] = '';
          $data['user'] = [];
        }
        $model = $this->model->updateOrCreate(['id' => $data['id']], $data);
        $objects[] = $model;
      }
      
      foreach ($objects as $object) {
        $update = [];
        $parent = $object->where('id', $object->parent_id)->first();
        if ($parent) {
          $update['parent_id'] = $parent->getKey();
          $update['parent'] = array_only($parent->toArray(), ['label', 'link', 'alias']);
        } else {
          $update['parent_id'] = '';
          $update['parent'] = [];
        }
        $update['parents'] = $object->parents ? $object->whereIn('id', $object->parents)->pluck($object->getKeyName())->toArray() : [];
        $update['children'] = $object->children ? $object->whereIn('id', $object->children)->pluck($object->getKeyName())->toArray() : [];
        $object->update($update);
      }
      $rec['synchronized_at'] = $now->format('U');
      $this->tableSync()->updateOrInsert(['alias' => $this->entity], $rec);
      
      if (method_exists($this, 'synchronized')) {
        return $this->synchronized($objects) && $this->logs(static::LOG_LEVEL_INFO, $message);
      } else {
        return $this->logs(static::LOG_LEVEL_INFO, $message);
      }
    } else {
      $this->logs(static::LOG_LEVEL_EMERGENCY, '数据同步（下行）失败，失败原因：'.($response ? $response['message'] : '未知'));
      return false;
    }
  }
  
  /**
   * 魔术调用
   * @access public
   * @param string $method
   * @param array $parameters
   * @return mixed
   * 
   * @throw BadMethodCallException
   */
  public function __call($method, $parameters) {
    if (!$this->model || !$this->model instanceof \Illuminate\Database\Eloquent\Model) {
      throw new BadMethodCallException(trans('modelfactory::exception.method_not_exists', compact('method')));
    }
    return call_user_func_array([$this->model, $method], $parameters);
  }
  
  /**
   * 获得同步配置表
   * @abstract
   * @access protected
   * @return mixed
   */
  abstract protected function tableSync();
  
  /**
   * 获得同步日志表
   * @abstract
   * @access protected
   * @return mixed
   */
  abstract protected function tableLogs();
  
}
