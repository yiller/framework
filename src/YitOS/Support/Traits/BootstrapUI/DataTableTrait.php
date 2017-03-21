<?php namespace YitOS\Support\Traits\BootstrapUI;

use RuntimeException;
use Illuminate\Http\Request;

/**
 * Ajax数据表分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\BootstrapUI
 */
trait DataTableTrait {
  use CommonTrait;
  
  /**
   * 数据表控制器构造函数
   * @access public
   * @return void
   * 
   * @throws RuntimeException
   */
  protected function initial() {
    // 数据源获取入口
    if (!property_exists($this, 'dataUrl') || !$this->data_url) {
      $this->data_url = action('\\'.get_class($this).'@listing');
    }
    // 数据表是否具备搜索功能
    if (property_exists($this, 'enabled_search') && $this->enabled_search) {
      $enabled = false;
      foreach ($this->columns as $column) if (isset($column['search']) && $column['search']) $enabled = true;
      $this->enabled_search = $enabled;
    } else {
      $this->enabled_search = false;
    }
    method_exists($this, 'customize') && $this->customize();
  }
  
  /**
   * 渲染数据表格UI
   * @access public
   * @return \Illuminate\Http\Response
   */
  public function index() {
    $template = 'ui::table.listing';
    property_exists($this, 'template') && view()->exists($this->template) && $template = $this->template;
    
    $data = [
      'name' => $this->name,
      'columns' => $this->columns,
      'data_url' => $this->data_url,
      'enabled_add' => $this->enabled_add,
      'add_url' => $this->page_add->angular_url,
      'enabled_search' => $this->enabled_search,
      'enabled_handles' => $this->enabled_handles
    ];
    
    return view($template, $data);
  }
  
  /**
   * 获得显示数据
   * @access public
   * @param \Illuminate\Http\Request $request
   * @return array|\Illuminate\Http\JsonResponse
   */
  public function listing(Request $request) {
    $action = $request->input('action', '');
    $listing = ['draw' => $request->get('draw', 0)];
    $builder = $this->builder();
    
    if ($action == 'filter') {
      
    }
    
    $listing['recordsTotal'] = $listing['recordsFiltered'] = $builder->count();
    $listing['data'] = [];
    $data = $builder->get();
    foreach ($data as $item) {
      if ($item instanceof \Illuminate\Database\Eloquent\Model) {
        $item = $item->attributesToArray();
      } elseif (!is_array($item)) {
        $item = [];
      }
      $line = [];
      $line[] = '';
      
      $item['__'] = isset($item['_id']) ? (string)$item['_id'] : '';
      
      //dd($this->columns);
      foreach ($this->columns as $column) {
        $val = isset($item[$column['bind']]) ? $item[$column['bind']] : '';
        if ($val && is_array($val) && $column['multi_language'] && ($languages = app('auth')->user()->team['languages'])) {
          $temp = '';
          foreach ($languages as $language) if (isset($val[$language]) && $val[$language]) { $temp = $val[$language]; break; }
          $val = $temp;
        }
        $func = isset($column['handle']) ? $column['handle'] : function($val, $item) { return $val; };
        $val = $func($val, $item);
        $align = isset($column['align']) ? $column['align'] : 'left';
        $line[] = '<span style="display:block;text-align:'.$align.';">'.$val.' </span>';
      }
      if ($this->enabled_handles) {
        $handles = $actions = '';
        $max = 5; $current = 1;
        foreach ($this->handles as $handle) {
          $func = isset($handle['enabled']) ? $handle['enabled'] : function($item) { return true; };
          $enabled = $func($item);
          $width = isset($handle['width']) && $handle['width'] && is_string($handle['width']) ? $handle['width'] : '';
          
          $page = $handle['page'];
          $color = isset($handle['color']) && $handle['color'] && is_string($handle['color']) ? $handle['color'] : '';
          $icon = isset($handle['icon']) && $handle['icon'] && is_string($handle['icon']) ? $handle['icon'] : '';
          $label = isset($handle['label']) && $handle['label'] && is_string($handle['label']) ? $handle['label'] : '';
          
          if ($current < $max) {
            if (isset($handle['mode']) && $handle['mode'] == 'modal') {
              $url = $page ? $page->url($item) : route($handle['route']).'?__='.$item['__'];
              $handles .= '<a'.($width?' data-width="'.$width.'"':'').' data-url="'.$url.'" data-toggle="modal" data-static="true" class="btn btn-xs '.$color.' modal-toggler'.($enabled?'':' disabled').'">'.($icon?'<i class="'.$icon.'"></i>':'').' '.$label.' </a>';
            } else {
              $url = $page ? $page->angular_url($item) : '#';
              $handles .= '<a class="btn btn-xs '.$color.($enabled?'':' disabled').'" href="'.$url.'"><i class="'.$icon.'"></i> '.$label.' </a>';
            }
            $current++;
          } else {
            if (!$enabled) continue;
            if (isset($handle['mode']) && $handle['mode'] == 'modal') {
              $url = $page ? $page->url($item) : route($handle['route']).'?__='.$item['__'];
              $actions .= '<li><a'.($width?' data-width="'.$width.'"':'').' data-url="'.$url.'" data-toggle="modal" data-static="true" class="modal-toggler">'.($icon?'<i class="'.$icon.'"></i>':'').' '.$label.' </a></li>';
            } else {
              $url = $page ? $page->angular_url($item) : '#';
              $actions .= '<li><a href="'.$angular_url.'"><i class="'.$icon.'"></i> '.$label.' </a></li>';
            }
          }
        }
        if ($actions) {
          $handles .= '<div class="btn-group">';
          $handles .= '<button class="btn btn-xs green-haze dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-share"></i> '.trans('ui.datahandle.text.actions').' <i class="fa fa-angle-down"></i></button>';
          $handles .= '<ul class="dropdown-menu pull-right" role="menu">'.$actions.'</ul>';
          $handles .= '</div>';
        }
        $line[] = $handles;
      }
      $listing['data'][] = $line;
    }
    return response()->json($listing);
  }
  
  
  
}
