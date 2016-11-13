<?php namespace YitOS\Support\Traits;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * 数据第三方远程同步分离类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits
 */
trait ExternalSyncTrait {
  
  /**
   * 获得扩展来源列表
   * @access protected
   * @param string $entity
   * @param string $format
   * @return array
   */
  protected function getExternalSourceOptions($entity, $format = '') {
    $sources = app('db')->table('_external_sources')->where('entities', 'all', [$entity])->pluck('label', 'alias');
    if ($format == 'single') {
      return $sources;
    }
    $options = [];
    foreach ($sources as $alias => $label) {
      $options[] = ['label' => $label, 'value' => $alias];
    }
    return $options;
  }
  
  /**
   * 获得扩展接口
   * @access protected
   * @param string $alias
   * @return \YitOS\WebSocket\SyncConnector|null
   */
  protected function getExternalConnector($alias) {
    $source = app('db')->table('_external_sources')->where('alias', $alias)->first();
    if (!$source) {
      return null;
    }
    $connector = app('websocket')->driver($source['class']);
    if (!$connector || !($connector instanceof \YitOS\WebSocket\SyncConnector)) {
      return null;
    }
    return $connector;
  }
  
  /**
   * 信息同步页面
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   * 
   * @throws RuntimeException
   */
  public function getSync(Request $request) {
    if (!method_exists($this, 'synchronizing') || !method_exists($this, 'getSyncModel')) {
      throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
    }
    
    $data = $this->synchronizing($request);
    $model = isset($data['model']) ? $data['model'] : null;
    if (!$model || !$model instanceof \YitOS\Contracts\WebSocket\ExternalSyncModel || !$model->isExternal()) {
      throw new \RuntimeException(trans('websocket::exception.sync.model_not_supported'));
    }
    
    $template = isset($data['template']) ? $data['template'] : '';
    if (!$template || !View::exists($template)) {
      throw new \RuntimeException(trans('websocket::exception.sync.template_not_found'));
    }
    
    $data['url'] = action('\\'.get_class($this).'@getSync');
    $data['title'] = isset($data['title']) ? $data['title'].'（_ID：'.$model->_id.'）' : '';
    return view($template, $data);
  }
  
  
  /**
   * 详情同步逻辑
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   * 
   * @throws RuntimeException
   */
  protected function postSync(Request $request) {
    set_time_limit(360);
    $handle = $request->get('handle', 'detail');
    if ($handle != 'listings') {
      $handle = 'detail';
    }
    
    $__ = $request->get('__', '');
    if (!$__ && $handle == 'detail') {
      $listings = session('sync.listings', []);
      $listings && $id = array_shift($listings);
      session(['sync.listings' => $listings]);
    }
    if (!method_exists($this, 'getSyncModel')) {
      throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
    }
    $model = $this->getSyncModel($__, $handle);
    if (!$model || !$model instanceof \YitOS\Contracts\WebSocket\ExternalSyncModel || !$model->isExternal()) {
      throw new \RuntimeException(trans('websocket::exception.sync.model_not_supported'));
    }
    $connector = $this->getExternalConnector($model->getExternalSource());
    if (!$connector) {
      return response()->json(['status' => -1, 'message' => trans('websocket::exception.sync.api_not_supported', ['name' => $model->getExternalSource()])]);
    }
    
    $step = $request->get('step', '');
    if ($step == 'initial') { // 初始化，清空SESSION 并验证接口有效性
      $handle = $this->initialSync($connector, $model, $handle);
    } elseif ($step == 'listings') { // 根据分类获得实体列表
      $page = intval($request->get('page', 1));
      $handle = $this->listingsSync($connector, $model, $page);
    } elseif ($step == 'detail') { // 准备开始详情同步
      $handle = $this->detailSync($model);
    } elseif ($step == 'info') { // 基本信息
      $handle = $this->infoSync($connector, $model);
    } else {
      $method = $step ? lcfirst(studly_case($step)).'Sync' : 'uiSync';
      if (property_exists($this, $method)) {
        throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
      }
      $handle = $this->$method($connector, $model, $handle);
      if (substr($handle,-4) == 'CONT') {
        $handle = substr($handle,0,-4) . $this->contSync($model);
      }
    }
    return response()->json(compact('handle'));
  }
  
  /**
   * 显示同步界面
   * @access protected
   * @param \YitOS\WebSocket\SyncConnector $connector
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @param string $handle
   * @return string
   */
  protected function uiSync(\YitOS\WebSocket\SyncConnector $connector, \YitOS\Contracts\WebSocket\ExternalSyncModel $model, $handle) {
    $url = action('\\'.get_class($this).'@postSync');
    return view('websocket::js.synchronize', compact('url', 'handle', 'model'))->render();
  }
  
  /**
   * 同步初始化
   * @access protected
   * @param \YitOS\WebSocket\SyncConnector $connector
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @param string $handle
   * @return string
   */
  protected function initialSync(\YitOS\WebSocket\SyncConnector $connector, \YitOS\Contracts\WebSocket\ExternalSyncModel $model, $handle) {
    session(['sync.listings' => [],'sync.size' => 0, 'sync.entity' => '']);
    $cells = [
      ['width' => '10%', 'text' => '实体标识'],
      ['width' => '15%', 'text' => '实体来源'],
      ['width' => '', 'text' => '同步实体'],
      ['width' => '25%', 'text' => '同步结果'],
    ];
    if ($handle == 'listings') {
      $handle  = "table_head(".json_encode($cells).");";
      $handle .= "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, '【分类列表】'.$model->title, '获得列表']).");";
      $handle .= "modal_layout();";
      $handle .= "listings('".$model->_id."', 1);";
    } else {
      $handle  = "table_head(".json_encode($cells).");";
      $handle .= "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, '【实体详情】'.$model->title, '获得详情']).");";
      $handle .= "modal_layout();";
      $handle .= "detail('".$model->_id."', 'detail');";
    }
    return $handle;
  }
  
  /**
   * 远程同步列表
   * @access protected
   * @param \YitOS\WebSocket\SyncConnector $connector
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @param integer $page
   * @return string
   */
  protected function listingsSync(\YitOS\WebSocket\SyncConnector $connector, \YitOS\Contracts\WebSocket\ExternalSyncModel $model, $page) {
    $now = Carbon::now()->format('U');
    if ($model->synchronized_at && $now - $model->synchronized_at < 600) {
      $handle  = "line_status('".$model->_id."', 'warning', ".json_encode(['', '', '', '忽略，上次同步时间：'.Carbon::createFromTimestamp($model->synchronized_at)->format(Carbon::DEFAULT_TO_STRING_FORMAT)]).");";
      $handle .= $this->contSync();
      return $handle;
    }
    $id = $model->getExternalId();
    $entities = []; $next = false;
    list($entities, $next) = $connector->listings(compact('id', 'page'));
    $listings = session('sync.listings', []);
    if ($entities) {
      foreach ($entities as $entity) {
        $entity['category'] = array_only($model->toArray(), ['_id', 'label']);
        dd($entity);
        $id = $this->saveSyncModel($entity);
        $id && !in_array($id, $listings) && $listings[] = $id;
      }
    } else {
      $next = false;
    }
    session(['sync.listings' => $listings]);
    if ($next) {
      $handle  = "line_status('".$model->_id."', 'loading', ".json_encode(['', '', '', '分类列表第 '.$page.' 页抓取成功']).");";
      $handle .= "listings('".$model->_id."',".($page+1).");";
    } else {
      session([
        'sync.size' => count($listings),
        'sync.entity' => $model->_id,
      ]);
      $handle  = "line_status('".$model->_id."', 'success', ".json_encode(['', '', '', '分类列表抓取成功（实体总计：'.count($listings).'）']).");";
      $handle .= $this->contSync();
    }
    return $handle;
  }
  
  /**
   * 远程同步详情（开始）
   * @access protected
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @return string
   */
  protected function detailSync(\YitOS\Contracts\WebSocket\ExternalSyncModel $model) {
    $title = '【实体详情】'.$model->title;
    $listings = session('sync.listings', []);
    if (session('sync.entity', '')) {
      $size = intval(session('sync.size', 0));
      $no = str_pad($size - count($listings),strlen($size.''),'0',STR_PAD_LEFT).'/'.str_pad($size,strlen($size.''),'0',STR_PAD_LEFT);
      $title .= '（'.$no.'）';
    }
    $handle  = "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $model->getExternalSource(), $title, '获得基本信息']).");";
    $handle .= "modal_layout();";
    $handle .= "detail('".$model->_id."', 'info')";
    return $handle;
  }
  
  /**
   * 远程同步详情（信息）
   * @access protected
   * @param \YitOS\WebSocket\SyncConnector $api
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @return string
   */
  protected function infoSync(\YitOS\WebSocket\SyncConnector $api, \YitOS\Contracts\WebSocket\ExternalSyncModel $model) {
    $now = Carbon::now()->format('U');
    $info = $api->detail(['id' => $model->getExternalId()]);
    if ($info && $model->fill($info)->save()) {
      if (method_exists($this, 'extraSync')) { // 需要更多的同步细节
        $handle  = "line_status('".$model->_id."', 'loading', ".json_encode(['', '', '', '正在同步额外详情']).");";
        $handle .= "detail('".$model->_id."', 'extra')";
      } else {
        $handle  = "line_status('".$model->_id."', 'success', ".json_encode(['', '', '', '保存基本信息成功']).");";
        $handle .= $this->contSync($model);
      }
    } elseif ($info) {
      $handle  = "line_status('".$model->_id."', 'danger', ".json_encode(['', '', '', '保存基本信息失败']).");";
      $handle .= $this->contSync($model);
    } else {
      $handle  = "line_status('".$model->_id."', 'danger', ".json_encode(['', '', '', '获取基本信息失败']).");";
      $handle .= $this->contSync($model);
    }
    return $handle;
  }
  
  /**
   * 下一个实体或结束？
   * @access protected
   * @param mixed $model
   * @return string
   */
  protected function contSync($model = null) {
    if ($model) {
      $model->synchronized_at = Carbon::now()->format('U');
      $model->save();
    }
    $id = trim(session('sync.entity', ''));
    if (!$id) { // 并非列表，结束同步
      return 'enable_buttons();';
    }
    
    $category = $this->getSyncModel($id, 'listings');
    $listings = session('sync.listings', []);
    if ($listings) { // 还有实体未同步
      return 'detail();';
    } else { // 同步已完成
      $category->synchronized_at = Carbon::now()->format('U');
      $category->save();
      return "enable_buttons();";
    }
  }
  
}
