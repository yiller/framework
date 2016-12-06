<?php namespace YitOS\Support\Traits\WebSocket\ExternalSync;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use YitOS\WebSocket\ExternalSync\Spider as ExternalSyncConnectorContract;
use YitOS\Contracts\WebSocket\ExternalSync\Model as ExternalSyncModelContract;

/**
 * 数据第三方远程同步分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\WebSocket\ExternalSync
 */
trait ControllerTrait {
  
  /**
   * 获得扩展来源列表
   * @access protected
   * @param string $entity
   * @return array
   */
  protected function getSyncSources($entity) {
    return app('db')->table('_external_sources')->where('entities', 'all', [$entity])->pluck('label', 'alias');
  }
  
  /**
   * 获得扩展接口
   * @access protected
   * @param string $alias
   * @return ExternalSyncConnectorContract|null
   */
  protected function getSyncConnector($alias) {
    $source = app('db')->table('_external_sources')->where('alias', $alias)->first();
    if (!$source) {
      return null;
    }
    $connector = app('websocket')->driver($source['class']);
    if ($connector instanceof ExternalSyncConnectorContract) {
      $connector->label = $source['label'] ?: $connector->label;
      $connector->alias = $source['alias'] ?: $connector->alias;
    } else {
      $connector = null;
    }
    return $connector;
  }
  
  /**
   * 获得实体名字
   * @access protected
   * @param ExternalSyncModelContract $model
   * @param string $type
   * @return string
   */
  protected function getSyncUILabel(ExternalSyncModelContract $model, $type = 'detail') {
    $key = $model->getExternalUILabelKey();
    $element = $this->getSyncDriver($type)->elements($key);
    $multi_language = isset($element['multi_language']) ? boolval($element['multi_language']) : false;
    $label = $model->$key;
    // 多语言支持
    if ($multi_language && $label && ($languages = app('auth')->user()->team['languages'])) {
      $temp = '';
      foreach ($languages as $language) {
        if (isset($label[$language]) && $label[$language]) { $temp = $label[$language]; break; }
      }
      $label = $temp;
    }
    return $label;
  }
  
  /**
   * 信息同步页面
   * @access public
   * @param Request $request
   * @return \Illuminate\Http\Response
   * 
   * @throws RuntimeException
   */
  public function getSync(Request $request) {
    if (!method_exists($this, 'syncRenderring') || !method_exists($this, 'getSyncDriver')) {
      throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
    }
    
    $data = $this->syncRenderring($request);
    $model = isset($data['model']) ? $data['model'] : null;
    if (!$model || !$model instanceof ExternalSyncModelContract || !$model->isExternal()) {
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
   * @param Request $request
   * @return \Illuminate\Http\Response
   * 
   * @throws RuntimeException
   */
  protected function postSync(Request $request) {
    @set_time_limit(0);
    $handle = $request->get('handle', 'detail');
    if ($handle != 'listings') {
      $handle = 'detail';
    }
    $__ = $request->get('__', '');
    if (!$__ && $handle == 'detail') {
      $listings = session('sync.listings', []);
      $listings && $__ = array_shift($listings);
      session(['sync.listings' => $listings]);
    }
    if (!method_exists($this, 'getSyncDriver')) {
      throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
    }
    $model = $this->getSyncDriver($handle)->builder()->find($__);
    if (!$model || !$model instanceof ExternalSyncModelContract || !$model->isExternal()) {
      throw new \RuntimeException(trans('websocket::exception.sync.model_not_supported'));
    }
    $connector = $this->getSyncConnector($model->getExternalSource());
    if (!$connector) {
      return response()->json(['status' => -1, 'message' => trans('websocket::exception.sync.api_not_supported', ['name' => $model->getExternalSource()])]);
    }
    
    $now = Carbon::now()->format('U');
    $step = $request->get('step', 'ui');
    if ($step == 'ui') { // 加载远端同步JS
      $handle = $this->uiSync($model, $handle);
    } elseif ($step == 'initial') { // 初始化，清空SESSION并显示远端同步表格
      $handle = $this->initialSync($connector, $model, $handle);
    } elseif ($step == 'listings') {
      if ($model->synchronized_at && $now - $model->synchronized_at < 1) {
        $handle  = "line_status('".$model->_id."', 'warning', ".json_encode(['', '', '', '忽略，上次同步时间：'.Carbon::createFromTimestamp($model->synchronized_at)->format(Carbon::DEFAULT_TO_STRING_FORMAT)]).");";
        $handle .= $this->contSync();
      } else {
        try {
          extract($connector->listings($model));
          if (!$model->getExternalId()) {
            $model = $this->getSyncDriver('listings')->save(compact('__', 'external'));
          }
        } catch (\RuntimeException $e) {
          $handle  = "line_status('".$model->_id."', 'danger', ".json_encode(['', '', '', $e->getMessage()]).");";
          $handle .= $this->contSync();
        }
      }
    } elseif ($step == 'detail') {
      exit;
    } else {
      $method = $step ? lcfirst(studly_case($step)).'Sync' : '';
      if (!$method || method_exists($connector, $method)) {
        throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
      }
      if (substr($handle,-4) == 'CONT') {
        $handle = substr($handle,0,-4) . $this->contSync($model);
      }
      exit;
    }
    
    
    /*if ($step == 'initial') { // 初始化，清空SESSION 并验证接口有效性
      $handle = $this->initialSync($connector, $model, $handle);
    } elseif ($step == 'listings') { // 根据分类获得实体列表
      $page = intval($request->get('page', 1));
      $handle = $this->listingsSync($connector, $model, $page);
    } elseif ($step == 'detail') { // 准备开始详情同步
      $handle = $this->detailSync($connector, $model);
    } elseif ($step == 'info') { // 基本信息
      $handle = $this->infoSync($connector, $model);
    } else {
      $method = $step ? lcfirst(studly_case($step)).'Sync' : 'uiSync';
      if ($method == 'uiSync') {
        $handle = $this->uiSync($model, $handle);
      } elseif (method_exists($connector, $method)) {
        extract($connector->$method($model));
        $instance = $this->getSyncBuilder()->save($model->getAttributes());
        $instance || $handle = "line_status('".$model->_id."', 'danger', ".json_encode(['', '', '', '额外操作失败']).");CONT";
      } else {
        throw new \RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
      }
    }*/
    return response()->json(compact('handle'));
  }
  
  /**
   * 显示同步界面
   * @access protected
   * @param ExternalSyncModelContract $model
   * @param string $handle
   * @return string
   */
  protected function uiSync(ExternalSyncModelContract $model, $handle) {
    $url = action('\\'.get_class($this).'@postSync');
    return view('websocket::js.synchronize', compact('url', 'handle', 'model'))->render();
  }
  
  /**
   * 同步初始化
   * @access protected
   * @param ExternalSyncConnectorContract $connector
   * @param ExternalSyncModelContract $model
   * @param string $handle
   * @return string
   */
  protected function initialSync(ExternalSyncConnectorContract $connector, ExternalSyncModelContract $model, $handle) {
    session(['sync.listings' => [],'sync.size' => 0, 'sync.entity' => '']);
    $cells = [
      ['width' => '10%', 'text' => '实体标识'],
      ['width' => '20%', 'text' => '实体来源'],
      ['width' => '', 'text' => '同步实体'],
      ['width' => '25%', 'text' => '同步结果'],
    ];
    $label = $this->getSyncUILabel($model, $handle);
    if ($handle == 'listings') {
      $handle  = "table_head(".json_encode($cells).");";
      $handle .= "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, '【分类列表】'.$label, '获得列表']).");";
      $handle .= "modal_layout();";
      $handle .= "listings('".$model->_id."', 1);";
    } else {
      $handle  = "table_head(".json_encode($cells).");";
      $handle .= "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, '【实体详情】'.$label, '获得详情']).");";
      $handle .= "modal_layout();";
      $handle .= "detail('".$model->_id."', 'detail');";
    }
    return $handle;
  }
  
  /**
   * 远程同步列表
   * @access protected
   * @param ExternalSyncConnectorContract $connector
   * @param ExternalSyncModelContract $model
   * @param integer $page
   * @return string
   */
  protected function listingsSync(ExternalSyncConnectorContract $connector, ExternalSyncModelContract $model, $page) {
    $now = Carbon::now()->format('U');
    if ($model->synchronized_at && $now - $model->synchronized_at < 1) {
      $handle  = "line_status('".$model->_id."', 'warning', ".json_encode(['', '', '', '忽略，上次同步时间：'.Carbon::createFromTimestamp($model->synchronized_at)->format(Carbon::DEFAULT_TO_STRING_FORMAT)]).");";
      $handle .= $this->contSync();
      return $handle;
    }
    /*$id = $model->getExternalId();
    $entities = []; $next = false;
    list($entities, $next) = $connector->listings(compact('id', 'page'));*/
    try {
      extract($connector->listings($model->getExternalUrl()), $page);
    } catch (\RuntimeException $e) {
      $handle  = "line_status('".$model->_id."', 'danger', ".json_encode(['', '', '', $e->getMessage()]).");";
      $handle .= $this->contSync();
      return $handle;
    }
    
    
    $listings = session('sync.listings', []);
    if ($entities) {
      foreach ($entities as $entity) {
        $entity['category_id'] = $model->_id;
        $instance = $this->getSyncBuilder()->save($entity);
        if (!$instance) {
          continue;
        }
        !in_array($instance->_id, $listings) && $listings[] = $instance->_id;
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
   * @param \YitOS\WebSocket\SyncConnector $connector
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @return string
   */
  protected function detailSync(\YitOS\WebSocket\SyncConnector $connector, \YitOS\Contracts\WebSocket\ExternalSyncModel $model) {
    $column = $this->getSyncBuilder('detail')->elements('name');
    $languages = app('auth')->user()->team['languages'];
    if ($languages && $model->name && is_array($model->name)) {
      $label = '';
      foreach ($languages as $language) {
        if (isset($model->name[$language]) && $model->name[$language]) { $label = $model->name[$language]; break; }
      }
    } else {
      $label = $model->name;
    }
    $title = '【实体详情】'.$label;
    $listings = session('sync.listings', []);
    if (session('sync.entity', '')) {
      $size = intval(session('sync.size', 0));
      $no = str_pad($size - count($listings),strlen($size.''),'0',STR_PAD_LEFT).'/'.str_pad($size,strlen($size.''),'0',STR_PAD_LEFT);
      $title .= '（'.$no.'）';
    }
    $handle  = "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, $title, '获得基本信息']).");";
    $handle .= "modal_layout();";
    $handle .= "detail('".$model->_id."', 'info')";
    return $handle;
  }
  
  /**
   * 远程同步详情（信息）
   * @access protected
   * @param \YitOS\WebSocket\SyncConnector $connector
   * @param \YitOS\Contracts\WebSocket\ExternalSyncModel $model
   * @return string
   */
  protected function infoSync(\YitOS\WebSocket\SyncConnector $connector, \YitOS\Contracts\WebSocket\ExternalSyncModel $model) {
    $now = Carbon::now()->format('U');
    $info = $connector->detail(['id' => $model->getExternalId()]);
    if (is_array($info) && $info) {
      $success = $model->fill($info)->save();
    } elseif (is_array($info)) {
      $success = true;
    } else {
      $success = false;
    }
    
    if ($success) {
      if (method_exists($connector, 'custom')) {
        $handle = $connector->custom($model);
      } else {
        $handle  = "line_status('".$model->_id."', 'success', ".json_encode(['', '', '', '保存基本信息成功']).");";
        $handle .= $this->contSync($model);
      }
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
    $__ = trim(session('sync.entity', ''));
    if (!$__) { // 并非列表，结束同步
      return 'enable_buttons();';
    }
    
    $listings = session('sync.listings', []);
    if ($listings) { // 还有实体未同步
      return 'detail();';
    } else { // 同步已完成
      $category = $this->getSyncBuilder('listings')->find($__);
      $category->synchronized_at = Carbon::now()->format('U');
      $category->save();
      return "enable_buttons();";
    }
  }
  
}
