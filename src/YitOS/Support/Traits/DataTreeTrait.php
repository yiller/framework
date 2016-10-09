<?php namespace YitOS\Support\Traits;

use RuntimeException;
use Illuminate\Http\Request;

/**
 * Ajax结构树分离类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits
 */
trait DataTreeTrait {
  
  /**
   * 加载配置
   * 
   * @access protected
   * @return array
   * 
   * @throws RuntimeException
   */
  protected function configDT() {
    if (!property_exists($this, 'name') || !property_exists($this, 'route') || 
        !method_exists($this, 'definedBuilder') || !method_exists($this, 'definedElements')) {
      throw new RuntimeException(trans('ui::exception.tree_not_supported'));
    }
    property_exists($this, 'columns') || $this->columns = [];
    method_exists($this, 'definedColumns') && $this->columns = $this->definedColumns();
    if (!is_array($this->columns) || empty($this->columns)) {
      throw new RuntimeException(trans('ui::exception.tree_not_supported'));
    }
    $config = [];
    $config['name'] = $this->name;
    $config['data_url'] = property_exists($this, 'dataUrl') ? $this->dataUrl : action('\\'.get_class($this).'@listings');
    
    $elements = [];
    foreach ($this->columns as $key => $column) {
      $column['name'] = $key;
      if (!isset($column['bind']) || !is_string($column['bind'])) { $column['bind'] = $key; }
      $elements[] = $column['bind'];
      $this->columns[$key] = $column;
    }
    $elements = array_only_by_sort($this->definedElements(), $elements);
    if (empty($elements)) {
      throw new RuntimeException(trans('ui::exception.tree_not_supported'));
    }
    $config['columns'] = [];
    foreach ($this->columns as $key => $column) {
      if (!isset($elements[$column['bind']])) { continue; }
      $config['columns'][$key] = array_merge($elements[$column['bind']], $column);
    }
    $config['columns'] = method_exists($this, 'columnsConfigured') ? $this->columnsConfigured($config['columns']) : $config['columns'];
    
    $config['add_enabled'] = false;
    if (app('routes')->hasNamedRoute($this->route.'.edit')) {
      $config['add_enabled'] = true;
      $config['add_url'] = route($this->route.'.edit');
    }
    $config['handle_enabled'] = false;
    if (app('routes')->hasNamedRoute($this->route.'.handle')) {
      $config['handle_enabled'] = true;
      $config['handle_url'] = route($this->route.'.handle');
    }
    return $config;
  }
  
  /**
   * 渲染数据表格UI
   * 
   * @access public
   * @return \Illuminate\Http\Response
   */
  public function index() {
    $data = method_exists($this, 'listingsRenderring') ? $this->listingsRenderring($this->configDT()) : $this->configDT();
    $template = array_key_exists('template', $data) ? $data['template'] : 'ui::tree.listings';
    return view($template, $data);
  }
  
  /**
   * 根据父级ID获得节点列表
   * 
   * @access protected
   * @param \Illuminate\Database\Query\Builder $builder
   * @param string $parent_id
   * @param array $cookie
   * @return array
   */
  protected function listingsRetrieve($builder, $parent_id, $cookie = []) {
    $entities = [];
    $clone = $builder;
    $builder = $builder->where('parent_id', $parent_id);
    $recordsFiltered = $recordsTotal = $builder->count();
    $listings = $builder->orderBy('sort_order', 'asc')->orderBy('updated_at', 'desc')->get();
    foreach ($listings as $entity) {
      if (in_array($entity->getKey(), $cookie)) {
        $entity->is_active = 1;
        $entities[] = $entity;
        $vs = $this->listingsRetrieve($clone, $entity->getKey(), $cookie);
        foreach ($vs as $v) { $entities[] = $v; }
      } else {
        $entity->is_active = 0;
        $entities[] = $entity;
      }
    }
    return $entities;
  }
  
  /**
   * 获得显示数据
   * 
   * @access public
   * @param \Illuminate\Http\Request $request
   * @param \Illuminate\Database\Query\Builder $builder
   * @return array|\Illuminate\Http\JsonResponse
   */
  public function listings(Request $request) {
    $config = $this->configDT();
    $cookie_name = 'datatree';
    $cookie_value = $request->cookie($cookie_name);
    if (!$cookie_value) {
      $cookie_value = [];
    }
    
    $builder = method_exists($this, 'listingsRetrieving') ? $this->listingsRetrieving() : $this->definedBuilder();
    $parent_id = $request->input('_id', 0);
    $action = $request->input('action', 'open');
    $listings = [];

    if ($action == 'open') { // 展开
      $listings['draw'] = $request->get('draw', 0);
      $entities = $this->listingsRetrieve($builder, $parent_id, $cookie_value);
      $listings['recordsTotal'] = $listings['recordsFiltered'] = count($entities);
      
      $data = [];
      foreach ($entities as $entity) {
        if ($entity instanceof \Illuminate\Database\Eloquent\Model) {
          $attributes = $entity->attributesToArray();
        } elseif (is_array($entity)) {
          $attributes = $entity;
        } else {
          $attributes = [];
        }
        $level = isset($attributes['parents']) ? count($attributes['parents']) : 0;
        $is_leaf = isset($attributes['children']) ? empty($attributes['children']) : true;
        $is_active = isset($attributes['is_active']) ? $attributes['is_active'] : false;
        method_exists($this, 'retrieveDataRow') && $attributes = $this->retrieveDataRow($attributes);
        $line = [];
        if ($is_leaf) {
          $line[] = '<i class="fa fa-file font-default" style="display:block;text-align:center;margin-top:4px;"></i>';
        } else {
          $folder = $is_active ? 
                  '<a href="javascript:;" class="tree-anchor" style="display:block;text-align:center;margin-top:2px;"><i class="fa fa-lg font-dark fa-folder-open"></i></a>' : 
                  '<a href="javascript:;" class="tree-anchor" style="display:block;text-align:center;margin-top:2px;"><i class="fa fa-lg font-dark fa-folder"></i></a>';
          $line[] = $folder . '<i class="fa font-dark fa-spin fa-spinner" style="display:none;text-align:center;margin-top:5px;"></i>';
        }
        $first_cell = true;
        foreach ($config['columns'] as $column) {
          $name = $column['name'];
          $bind = $column['bind'];
          $func = isset($column['handle']) ? $column['handle'] : function($value, $attrs) { return $value; };
          $value = isset($attributes[$bind]) ? $func($attributes[$bind], $attributes) : $func('', $attributes);
          $value = array_key_exists('align', $column) ? ('<span style="display:block;text-align:'.$column['align'].';">'.$value.'</span>') : $value;
          if ($first_cell) {
            $value = '<span style="display:block;text-indent:'.($level*2).'em;">'.$value.'</span>';
            $value .= '<input type="hidden" name="_id" value="'.$attributes['_id'].'">';
            $value .= '<input type="hidden" name="_parent" value="'.$attributes['parent_id'].'">';
            $first_cell = false;
          }
          $line[] = $value;
        }

        if ($config['handle_enabled']) {
          if (!isset($attributes['handles'])) {
            $buttons = '';
          } elseif (is_string($attributes['handles'])) {
            $buttons = $attributes['handles'];
          } elseif (is_array($attributes['handles'])) {
            $handles = $actions = '';
            $max_buttons = 3; $current_button = 1;
            foreach ($attributes['handles'] as $handle) {
              if ($current_button < $max_buttons && is_array($handle)) { // handles
                list($link, $mode, $icon, $color, $label) = $handle;
                if ($mode == 'normal') {
                  $handles .= '<a class="btn btn-xs '.$color.'" href="'.$link.'"><i class="'.$icon.'"></i> '.$label.' </a>';
                } else {
                  $handles .= '<a'.($mode=='full'?' data-width="full"':'').' data-url="'.$link.'" data-toggle="modal" data-static="true" class="btn btn-xs '.$color.' modal-toggler"><i class="'.$icon.'"></i> '.$label.' </a>';
                }
                
                $current_button++;
              } elseif (is_string($handle) && $handle == '-') { // actions
                $actions && $actions .= '<li class="divider"> </li>';
              } elseif (is_array($handle)) {
                list($link, $mode, $icon, $color, $label) = $handle;
                if ($mode == 'normal') {
                  $actions .= '<li><a href="'.$link.'"><i class="'.$icon.'"></i> '.$label.' </a></li>';
                } else {
                  $actions .= '<li><a'.($mode=='full'?' data-width="full"':'').' data-url="'.$link.'" data-toggle="modal" class="modal-toggler"><i class="'.$icon.'"></i> '.$label.' </a></li>';
                }
              }
            }
            $buttons = $handles;
            if ($actions) {
              $buttons .= '<div class="btn-group">';
              $buttons .= '<button class="btn btn-xs green-haze dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-share"></i> '.trans('ui.datahandle.text.actions').' <i class="fa fa-angle-down"></i></button>';
              $buttons .= '<ul class="dropdown-menu pull-right" role="menu">'.$actions.'</ul>';
              $buttons .= '</div>';
            }
          }
          $line[] = $buttons;
        }
        $data[] = $line;
      }
      $listings['data'] = $data;
      
      // COOKIE更新，增加节点
      $parent_id && !in_array($parent_id, $cookie_value) && $cookie_value[] = $parent_id;
    } else {
      // COOKIE更新，删除节点
      $current = $builder->find($parent_id);
      $diff = $current ? $current->children : [];
      $diff[] = $parent_id;
      $cookie_value = array_diff($cookie_value, $diff);
    }
    $listings['parent_id'] = $parent_id;
    return response()->json($listings)->cookie($cookie_name, $cookie_value);
  }
  
}
