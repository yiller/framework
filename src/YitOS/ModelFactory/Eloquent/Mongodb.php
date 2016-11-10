<?php namespace YitOS\ModelFactory\Eloquent;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Mongodb\Eloquent\Model as BaseModel;
use YitOS\Support\Relations\ParentChildrenTrait;
use YitOS\Support\Facades\WebSocket;
use YitOS\ModelFactory\Eloquent\Model as ModelContract;

/**
 * Mongodb数据库模型基类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\ModelFactory\Eloquent
 * @abstract
 * @see \YitOS\ModelFactory\Eloquent\Model
 * @see \Jenssegers\Mongodb\Eloquent\Model
 * @see \Illuminate\Database\Eloquent\Model
 */
abstract class Mongodb extends BaseModel implements ModelContract {
  use ParentChildrenTrait;
  
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
   * 所有属性都允许批量赋值
   * @var bool
   */
  protected static $unguarded = true;
  
  /**
   * 是否需要同步上传
   * @var bool 
   */
  protected $needSyncUpload = false;
  
  /**
   * 实体结构
   * @var array
   */
  protected $elements = [];
  
  /**
   * 初始化模型数据
   * @access public
   * @param string $entity
   * @param integer $duration
   * @return \YitOS\ModelFactory\Eloquent\Mongodb
   */
  public function initial($entity, $duration) {
    $this->entity = $entity;
    $this->duration = intval($duration);
    return $this;
  }
  
  /**
   * 获得实体名字
   * @access public
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }
  
  /**
   * 获得同步配置表
   * @access protected
   * @return \Jenssegers\Mongodb\Collection
   */
  protected function getSyncTable() {
    return $this->getConnection()->collection('_sync');
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
    $timestamp = $timestamp ?: Carbon::now()->format('U');
    $log = [
      'entity' => $this->entity,
      'level' => $level,
      'message' => $message,
      'timestamp' => $timestamp
    ];
    if (app('auth')->user()) {
      $log['user_id'] = app('auth')->user()->_id;
    } else {
      $log['user_id'] = '';
    }
    return $this->getConnection()->collection('_sync_logs')->insert($log);
  }
  
  /**
   * 元素定义
   * @access public
   * @return array
   */
  public function elements() {
    if ($this->elements) {
      return $this->elements;
    }
    Cache::forget('elements_defined_'.$this->entity);
    $elements = Cache::rememberForever('elements_defined_'.$this->entity, function() {
      $response = WebSocket::sync_elements(['entity' => $this->entity]);
      return $response && $response['code'] == 1 ? $response['elements'] : [];
    });
    return $this->elements = $elements;
  }
  
  /**
   * 储存数据
   * 
   * @access public
   * @param  array  $options
   * @return \YitOS\ModelFactory\Eloquent|bool
   */
  public function save(array $options = []) {
    $query = $this->newQueryWithoutScopes();
    
    if ($this->fireModelEvent('saving') === false) {
      return false;
    }

    if ($this->needSyncUpload && !$this->syncUpload()) {
      return false;
    }
    
    if ($this->exists) {
      $saved = $this->performUpdate($query, $options);
    } else {
      $saved = $this->performInsert($query, $options);
    }
    
    if ($saved) {
      $this->finishSave($options);
    }
    
    return $saved;
  }
  
  /**
   * 是否需要同步上传
   * @accss public
   * @param bool $need
   * @return \YitOS\MModelFactory\Eloquent\Model
   */
  public function needSyncUpload($need = true) {
    $this->needSyncUpload = $need;
    return $this;
  }
  
  /**
   * 数据同步（上行）
   * @access public
   * @return bool
   */
  public function syncUpload() {
    if (!$this->entity) {
      Log::notice('数据同步（上行）中止，未定义实体类型', ['classname' => get_class($this)]);
      return true;
    }
    
    $now = Carbon::now();
    $entity = $this->entity;
    $data = $this->attributes;
    
    $data['id'] = isset($data['_id']) && ($model = static::find($data['_id'])) ? intval($model->id) : 0;
    $data['parent_id'] = isset($data['parent_id']) && ($parent = static::find($data['parent_id'])) ? intval($parent->id) : 0;
    $data['sort_order'] = isset($data['sort_order']) ? intval($data['sort_order']) : 0;
    unset($data['_id'], $data['_token'], $data['method']);

    $this->logs(static::LOG_LEVEL_INFO, '数据同步（上行）开始', $now->format('U'));
    $response = WebSocket::sync_upload(compact('entity', 'data'));

    if ($response && $response['code'] == 1) {
      $message = '数据同步（上行）成功，基库编号： #'.$response['data']['id'];
      $data = $response['data'];
      $data['user_id'] = $data['account_id'];
      unset($data['account_id']);
      
      $parent = $this->where('id', $data['parent_id'])->first();
      if ($parent) {
        $data['parent_id'] = $parent->getKey();
        $data['parent'] = array_only($parent->toArray(), ['label', 'link', 'alias']);
      } else {
        $data['parent_id'] = '';
        $data['parent'] = [];
      }
      $data['parents'] = $data['parents'] ? $this->whereIn('id', $data['parents'])->pluck($this->getKeyName())->toArray() : [];
      $data['children'] = $data['children'] ? $this->whereIn('id', $data['children'])->pluck($this->getKeyName())->toArray() : [];
      
      if (isset($this->attributes['_id'])) {
        $data['_id'] = $this->attributes['_id'];
      }
      $this->attributes = $data;
      return $this->logs(static::LOG_LEVEL_INFO, $message);
    } else {
      $this->logs(static::LOG_LEVEL_EMERGENCY, '数据同步（上行）失败，失败原因：'.($response ? $response['message'] : '未知'));
      return false;
    }
  }
  
  /**
   * 数据同步（下行）
   * 
   * @access public
   * @return bool
   */
  public function syncDownload() {
    $timestamp = 0;
    if (!$this->entity) {
      Log::notice('数据同步（下行）中止，未定义实体类型', ['classname' => get_class($this)]);
      return true;
    }

    $now = Carbon::now();
    $params = ['entity' => $this->entity];
    $rec = $this->getSyncTable()->where('alias', $this->entity)->first();
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
      $provider = Auth::guard()->getProvider();
      foreach ($response['data'] as $data) {
        $account_id = $data['account_id'];
        unset($data['account_id']);
        $data['user_id'] = $account_id;
        
        $model = $this->updateOrCreate(['id' => $data['id']], $data);
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
      $this->getSyncTable()->updateOrInsert(['alias' => $this->entity], $rec);
      
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

}
