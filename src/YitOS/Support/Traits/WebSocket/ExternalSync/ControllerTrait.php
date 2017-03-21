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
      throw new RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
    }
    
    $data = $this->syncRenderring($request);
    $model = isset($data['model']) ? $data['model'] : null;
    if (!$model || !$model instanceof ExternalSyncModelContract || !$model->isExternal()) {
      throw new RuntimeException(trans('websocket::exception.sync.model_not_supported'));
    }
    $template = isset($data['template']) ? $data['template'] : '';
    if (!$template || !View::exists($template)) {
      throw new RuntimeException(trans('websocket::exception.sync.template_not_found'));
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
    if (!method_exists($this, 'getSyncDriver')) {
      throw new RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
    }
    
    $__ = $request->get('__', '');
    if (!$__ && $handle == 'detail') {
      $entity = session('sync.entity', []);
      if (!$entity || !isset($entity['external']['source']) || !isset($entity['external']['id'])) {
        throw new RuntimeException(trans('websocket::exception.sync.entity_parameter_is_missing'));
      }
      $model = $this->getSyncDriver()->builder()->where(['external.enabled' => 1, 'external.source' => $entity['external']['source'], 'external.id' => $entity['external']['id']])->first();
      $model = $model ?: $this->getSyncDriver()->instance();
      unset($entity['__']);
      $model->fill($entity);
    } else {
      $model = $this->getSyncDriver($handle)->builder()->find($__);
    }
    
    if (!$model || !$model instanceof ExternalSyncModelContract || !$model->isExternal()) {
      throw new RuntimeException(trans('websocket::exception.sync.model_not_supported'));
    }
    $connector = $this->getSyncConnector($model->getExternalSource());
    if (!$connector) {
      return response()->json(['status' => -1, 'message' => trans('websocket::exception.sync.api_not_supported', ['name' => $model->getExternalSource()])]);
    }
    
    $now = Carbon::now()->format('U');
    $step = $request->get('step', 'initial');
    if ($step == 'initial') { // 初始化，加载远端同步JS，清空SESSION并显示远端同步表格
      $handle = $this->initialSync($model, $handle);
    } elseif ($step == 'ui') { // 显示表格行
      $handle = $this->uiSync($connector, $model, $handle);
    } elseif ($step == 'listings') {
      if ($model->synchronized_at && $now - $model->synchronized_at < 600) {
        $handle  = "line_status('".$model->_id."', 'warning', ".json_encode(['', '', '', '忽略，上次同步时间：'.Carbon::createFromTimestamp($model->synchronized_at)->format(Carbon::DEFAULT_TO_STRING_FORMAT)]).");CONT";
      } else {
        $handle = $this->listingsSync($connector, $model, $handle);
      }
    } elseif ($step == 'detail') {
      $handle = $this->detailSync($connector, $model);
    } else {
      $method = $step ? lcfirst(studly_case($step)).'Sync' : '';
      if (!$method || !method_exists($connector, $method)) {
        throw new RuntimeException(trans('websocket::exception.sync.controller_not_supported'));
      }
      $switch = $handle;
      try {
        extract($connector->$method($model));
        if ($switch == 'listings') {
          $sync_entities = session('sync.entities', []);
          $sync_listings = session('sync.listings', []);
          if (isset($entities)) {
            foreach ($entities as $entity) {
              $entity['category_id'] = $model->_id;
              $sync_entities[$entity['external']['source'].'.'.$entity['external']['id']] = $entity;
            }
          }
          if (isset($listings)) {
            foreach ($listings as $child) {
              in_array($child, $sync_listings) || $sync_listings[] = $child;
            }
          }
          session(['sync.entities' => $sync_entities, 'sync.listings' => $sync_listings]);
        } else {
          if (isset($entity)) {
            isset($entity['_id']) && $entity['__'] = (string)$entity['_id'];
            unset($entity['_id'], $entity['created_at'], $entity['updated_at']);
            session(['sync.entity' => $entity]);
          }
        }
      } catch (RuntimeException $e) {
        $handle  = "line_status('', 'danger', ".json_encode(['', '', '', $e->getMessage()]).");CONT";
      }
    }
    if (substr($handle,-4) == 'CONT') {
      $handle = substr($handle,0,-4) . $this->contSync();
    }
    return response()->json(compact('handle'));
  }
  
  /**
   * 同步初始化
   * @access protected
   * @param ExternalSyncModelContract $model
   * @param string $handle
   * @return string
   */
  protected function initialSync(ExternalSyncModelContract $model, $handle) {
    $listing = $handle == 'listings' ? $model->_id : '';
    session(['sync.listing' => $listing, 'sync.listings' => [], 'sync.listing_current' => 0, 'sync.entities' => [], 'sync.entity_current' => 0, 'sync.entity' => []]);
    $url = action('\\'.get_class($this).'@postSync');
    $cells = [
      ['width' => '10%', 'text' => '实体标识'],
      ['width' => '15%', 'text' => '实体来源'],
      ['width' => '', 'text' => '同步实体'],
      ['width' => '25%', 'text' => '同步结果'],
    ];
    return view('websocket::js.synchronize', compact('url', 'handle', 'model', 'cells'))->render();
  }
  
  /**
   * 显示同步界面
   * @access protected
   * @param ExternalSyncConnectorContract $connector
   * @param ExternalSyncModelContract $model
   * @param string $handle
   * @return string
   */
  protected function uiSync(ExternalSyncConnectorContract $connector, ExternalSyncModelContract $model, $handle) {
    $label = $this->getSyncUILabel($model, $handle);
    if ($handle == 'listings') {
      $handle  = "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, '【实体列表】'.$label, '正在抓取页面并分析列表...']).");";
      $handle .= "modal_layout();";
      $handle .= "listings('".$model->_id."', 1);";
    } else {
      $listings = session('sync.entities', []);
      $current = intval(session('sync.entity_current', 0)) + 1;
      $no = '';
      if ($listings) {
        $size = count($listings);
        $length = strlen((string)$size);
        $no = str_pad((string)$current, $length, '0', STR_PAD_LEFT) . '/' . $size;
      }
      $handle  = "table_line('".$model->_id."', 'loading', ".json_encode([$model->getExternalId(), $connector->label, '【实体详情】('.$no.') '.$label, '正在抓取页面并分析实体...']).");";
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
   * @param string $handle
   * @return string
   */
  protected function listingsSync(ExternalSyncConnectorContract $connector, ExternalSyncModelContract $model, $handle) {
    try {
      $__ = $model->_id;
      extract($connector->listings($model));
      // 更新分类的扩展来源信息
      if (!$model->getExternalId()) {
        $model = $this->getSyncDriver('listings')->save(compact('__', 'external'));
      }
      // 实体暂存
      if (!isset($entities) || !$entities) {
        throw new RuntimeException('列表页面分析失败（未能正确解析实体信息）');
      }
      $sync_entities = session('sync.entities', []);
      foreach ($entities as $entity) {
        $entity['category_id'] = $model->_id;
        $sync_entities[$entity['external']['source'].'.'.$entity['external']['id']] = $entity;
      }
      // 子级列表
      $sync_listings = session('sync.listings', []);
      if (isset($listings)) {
        foreach ($listings as $child) {
          in_array($child, $sync_listings) || $sync_listings[] = $child;
        }
      }
      session(['sync.entities' => $sync_entities, 'sync.listings' => $sync_listings]);
    } catch (RuntimeException $e) {
      $handle  = "line_status('".$model->_id."', 'danger', ".json_encode(['', '', '', $e->getMessage()]).");CONT";
    }
    return $handle;
  }
  
  /**
   * 远程同步详情（开始）
   * @access protected
   * @param ExternalSyncConnectorContract $connector
   * @param ExternalSyncModelContract $model
   * @return string
   */
  protected function detailSync(ExternalSyncConnectorContract $connector, ExternalSyncModelContract $model) {
    try {
      extract($connector->detail($model));
      if (!isset($entity) || !$entity) {
        throw new RuntimeException('实体页面分析失败（未能正确解析实体信息）');
      }
      isset($entity['_id']) && $entity['__'] = (string)$entity['_id'];
      unset($entity['_id'], $entity['created_at'], $entity['updated_at']);
      session(['sync.entity' => $entity]);
    } catch (RuntimeException $e) {
      $handle  = "line_status('', 'danger', ".json_encode(['', '', '', $e->getMessage()]).");CONT";
    }
    return $handle;
  }
  
  /**
   * 下一个实体或结束？
   * @access protected
   * @return string
   */
  protected function contSync() {
    $listings = session('sync.listings', []);
    $listing_current = intval(session('sync.listing_current', 0));
    $listing = session('sync.listing', '');
    $entities = session('sync.entities', []);
    $entity_current = intval(session('sync.entity_current', 0));
    $entity = session('sync.entity', []);
    $now = Carbon::now()->format('U');
    /*if ($entity) { // 更新实体的同步时间
      $model = $this->getSyncDriver()->builder()->find($entity['__']);
      $model->synchronized_at = Carbon::now()->format('U');
      $model->save();
    }*/
    if ($listing) { // 列表同步
      if ($listings && $listing_current < count($listings)) {
        $listing = $listings[$listing_current++];
        session(['sync.listing_current' => $listing_current]);
        return "ui('listings', '".$listing."');";
      }
      if ($entity) { // 实体存在，则回写
        $entities[$entity['external']['source'].'.'.$entity['external']['id']] = $entity;
        $entity_current++;
      }
      if ($entity_current < count($entities)) { // 下一个实体
        $entity = array_slice(array_values($entities), $entity_current, 1)[0];
        session(['sync.entities' => $entities, 'sync.entity_current' => $entity_current, 'sync.entity' => $entity]);
        return "ui('detail');";
      }
      if ($entities) {
        // 列表同步完成，需要保存
        // 先同步实体，并更新实体同步时间
        $models = $this->getSyncDriver()->saveMany($entities);
        foreach ($models as $model) {
          $model->synchronized_at = $now;
          $model->save();
        }
        // 再更新列表同步时间
        $model = $this->getSyncDriver('listings')->builder()->find($listing);
        $model->synchronized_at = $now;
        $model->save();
      }
    } else { // 实体同步
      
    }
    /*if ($size && $listings) { // 列表同步，还有实体尚未同步
      $entity = array_shift($listings);
      session(['sync.listings' => $listings, 'sync.entity' => $entity]);
      return "ui('detail');";
    } elseif ($size) { // 列表同步完成
      $model = $this->getSyncDriver('listings')->builder()->find($entity['category_id']);
      $model->synchronized_at = Carbon::now()->format('U');
      $model->save();
    }*/
    return 'enable_buttons();';
    /*exit;
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
    }*/
  }
  
}
