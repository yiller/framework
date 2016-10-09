<?php namespace YitOS\Support\Traits;

use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * Ajax数据表分离类
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits
 */
trait DataTableTrait {
  
  /**
   * 是否启用批量处理
   * 
   * @var bool
   */
  protected $batchEnabled = false;
  
  /**
   * 是否启用搜索
   * 
   * @var bool
   */
  protected $searchEnabled = false;
  
  /**
   * 默认的可用搜索模式
   * 
   * @var array 
   */
  protected $searchMode = ['text', 'select', 'range', 'date'];
  
  /**
   * 搜索配置
   * 
   * @var array
   */
  protected $search = [];
  
  /**
   * 加载配置
   * 
   * @access protected
   * @return array
   * 
   * @throws RuntimeException
   */
  protected function config() {
    if (!property_exists($this, 'name') || !property_exists($this, 'columns') || !method_exists($this, 'handleDataRow')) {
      throw new RuntimeException('exception.dataTable.controllerNotSupport');
    }
    $config = [];
    $config['name'] = $this->name;
    $config['data_url'] = property_exists($this, 'dataUrl') ? $this->dataUrl : action('\\'.get_class($this).'@listings');
    $config['columns'] = [];
    $search_enabled = false;
    $mapping = (property_exists($this, 'mapping') && is_array($this->mapping)) ? $this->mapping : [];
    foreach ($this->columns as $name => $column) {
      $column['name'] = $name;
      $column['key'] = array_key_exists($name, $mapping) ? $mapping[$name] : $name;
      if (array_key_exists('search', $column) && is_array($column['search']) && array_key_exists('mode', $column['search']) && in_array($column['search']['mode'], $this->searchMode)) {
        if ($column['search']['mode'] == 'select') {
          $options = [];
          if (array_key_exists('options', $column['search']) && is_array($column['search']['options'])) {
            $options = $column['search']['options'];
          } else {
            $method = (array_key_exists('options', $column['search']) && is_string($column['search']['options'])) ? $column['search']['options'] : 'get'.Str::studly($name).'Options';
            $options = method_exists($this, $method) ? $this->$method() : [];
          }
          if ($options) {
            $column['search']['options'] = $options;
            $search_enabled = true;
          } else {
            unset($column['search']);
          }
        } else {
          $search_enabled = true;
        }
      } else {
        unset($column['search']);
      }
      
      $method = 'handle'.Str::studly($name);
      if (method_exists($this, $method)) {
        $column['handle'] = $method;
      }
      
      $config['columns'][$name] = $column;
    }
    
    $config['typeahead_url'] = '';
    if ($search_enabled) {
      $config['typeahead_url'] = property_exists($this, 'typeaheadUrl') ? $this->typeaheadUrl : action('\\'.get_class($this).'@typeahead');
    }
    
    $config['search_enabled'] = $search_enabled;
    $config['batch_enabled'] = (property_exists($this, 'batch') && $this->batch);
    $config['batch'] = property_exists($this, 'batch') ? $this->batch : [];
    
    $config['handle_enabled'] = (property_exists($this, 'handles') && $this->handles);
    $config['handles'] = property_exists($this, 'handles') ? $this->handles : [];
    
    return $config;
  }
  
  /**
   * 渲染数据表格 GET
   * 获得显示数据 POST
   * 
   * @access protected
   * @param \Illuminate\Http\Request $request
   * @param \Illuminate\Database\Query\Builder $builder
   * @return array|\Illuminate\Http\JsonResponse
   */
  protected function listings(Request $request, $builder) {
    $config = $this->config();
    if ($request->method() == 'POST') {
      $draw = $request->get('draw', 0);
      $recordsTotal = $builder->count()*100;
      
      if ($config['search_enabled'] && $request->has('action') && $request->input('action') == 'filter') {
        foreach ($config['columns'] as $column) {
          if (!array_key_exists('search', $column)) {
            continue;
          }
          $name = $column['name'];
          switch ($column['search']['mode']) {
            case 'text':
              $value = trim($request->input($name, ''));
              if (strlen($value) == 0) break;
              if (array_key_exists('text', $column['search']) && $column['search']['text'] > 0) {
                $builder->where($column['key'], $value);
              } else {
                $builder->where($column['key'], 'like', "%{$value}%");
              }
              break;
            case 'date':
              $start = trim($request->input("{$name}_from", ''));
              $end = trim($request->input("{$name}_to", ''));
              if (strlen($start) > 0 && strlen($end) > 0) {
                $builder->where($column['key'], '>=', $start.' 00:00:00')
                        ->where($column['key'], '<=', $end.' 23:59:59');
              } elseif (strlen($start) > 0) {
                $builder->where($column['key'], '>=', $start.' 00:00:00');
              } elseif (strlen($end) > 0) {
                $builder->where($column['key'], '<=', $end.' 23:59:59');
              }
              break;
            case 'range':
              $start = trim($request->input("{$name}_from", ''));
              $end = trim($request->input("{$name}_to", ''));
              if (strlen($start) > 0 && strlen($end) > 0) {
                $builder->where($column['key'], '>=', intval($start))
                        ->where($column['key'], '<=', intval($end));
              } elseif (strlen($start) > 0) {
                $builder->where($column['key'], '>=', intval($start));
              } elseif (strlen($end) > 0) {
                $builder->where($column['key'], '<=', intval($end));
              }
              break;
            case 'select':
              $value = trim($request->input($name, ''));
              if (strlen($value) == 0 || !array_key_exists($value, $column['search']['options'])) break;
              $builder->where($column['key'], $value);
              break;
          }
        }
      }
      
      $recordsFiltered = $builder->count();
      
      // 偏移与长度
      $offset = intval($request->input('start', 0));
      $length = intval($request->input('length', -1));

      $entities = $builder->get();
      $data = [];
      foreach ($entities as $entity) {
        $entity = $this->handleDataRow($entity);
        $line = [];
        if ($config['batch_enabled']) {
          $line[] = '<input type="checkbox" name="id[]" value="'.$entity->_id.'">';
        }
        foreach ($config['columns'] as $column) {
          $name = $column['name'];
          if (array_key_exists('align', $column)) {
            $line[] = '<span style="display:block;text-align:'.$column['align'].';">'.$entity->$name.'</span>';
          } else {
            $line[] = $entity->$name;
          }
        }
        if ($config['handle_enabled']) {
          $buttons = [];
          foreach ($config['handles'] as $key => $handle) {
            
          }
          $line[] = implode('&nbsp;', $buttons);
        }
        $data[] = $line;
      }
      
      $data = compact('draw', 'recordsTotal', 'recordsFiltered', 'data');
      return response()->json($data);
    }
    return $config;
  }
  
  /**
   * 获得搜索下拉菜单补全列表
   * 
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function typeahead(Request $request) {
    $key = $request->get('name', '');
    $query = $request->get('query', '');
    $condition = [];
    $field = '';
    if ($this->searchEnabled) {
      foreach ($this->search as $name => $options) {
        if ($options['mode'] != 'text' || $name != $key) continue;
        $field = array_key_exists('field', $options) ? $options['field'] : $name;
        $condition[$field] = ['like', "%{$query}%"];
      }
    }
    method_exists($this, 'handleListingsCondition') && ($condition = $this->handleListingsCondition($condition));
    $entities = $this->getListingsData($condition, 0, 10, $field.' as value');
    $values = [];
    foreach ($entities as $entity) {
      $values[] = $entity['value'];
    }
    return response()->json($values);
  }
  
  /**
   * 根据索引获得列信息
   * 
   * @access protected
   * @param integer $index
   * @return array
   */
  protected function getColByIndex($index) {
    property_exists($this, 'batch') && $this->batch && ($index--);
    if ($index < 0 || $index >= count($this->columns)) {
      return [];
    }
    $i = 0; $column = [];
    foreach ($this->columns as $name => $col) {
      $col['name'] = $name;
      if ($index == $i) {
        $column = $col;
        break;
      }
      $i++;
    }
    return $column;
  }
  
}
