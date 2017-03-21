<?php namespace YitOS\Support\Html;

use RuntimeException;

/**
 * html分析工具
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Html
 */
class Document {
  
  /**
   * 用于解析的html
   * @var string
   */
  protected $html = '';
  
  /**
   * 允许的html标签
   * @var array
   */
  protected $allow_tags = ['h1','h2','h3','h4','h5','h6','blockquote','div','p','span','b','i','u','strong','font','header','footer','section','aside','img','a','ul','ol','li','dl','dt','dd','form','input','select','option','label','fieldset','legend'];
  
  /**
   * 节点的属性值列表
   * @var array
   */
  protected $attributes = [];
  
  /**
   * 生成查询工具
   * @access public
   * @param string $html
   * @return \YitOS\Support\Html\Document
   */
  public function __construct($html) {
    $html = str_replace(["\r\n", "\n"], '', $html);
    $this->html = $html;
  }
  
  /**
   * 根据字符串路径生成XPath
   * @access protected
   * @param string $path
   * @return array
   * 
   * @throws RuntimeException
   */
  protected function getXPath($path) {
    if (!$path || !is_string($path)) {
      throw new RuntimeException('查询节点参数错误');
    }
    $xpath = [];
    foreach (explode('$$$', preg_replace('/\s+/i', '$$$', $path)) as $str) {
      if (!$str) { continue; }
      $tag = $identity = ''; $classname = [];
      if (strpos($str,'#') !== false) {
        $tag = substr($str,0,stripos($str,'#'));
        if ($tag && !in_array($tag, $this->allow_tags)) {
          throw new RuntimeException('查询节点参数错误：未知的标签（'.$tag.'）');
        }
        $substr = substr($str, stripos($str,'#')+1);
        if (strpos($substr,'#') !== false) {
          throw new RuntimeException('查询节点参数错误：二次ID定义（'.$str.'）');
        }
        if (strpos($substr,'.') === false) {
          $identity = trim($substr);
        } else {
          $identity = substr($substr,0,strpos($substr,'.'));
          $temp = explode('.',substr($substr,strpos($substr,'.')+1));
          foreach ($temp as $tmp) {
            if (!$tmp) { continue; }
            $classname[] = $tmp;
          }
        }
        if (!$identity) {
          throw new RuntimeException('查询节点参数错误：未知的ID定义（'.$str.'）');
        }
      } elseif (strpos($str,'.') !== false) {
        $tag = substr($str,0,stripos($str,'.'));
        if ($tag && !in_array($tag, $this->allow_tags)) {
          throw new RuntimeException('查询节点参数错误：未知的标签（'.$tag.'）');
        }
        $substr = substr($str, stripos($str,'.')+1);
        if (!$substr) {
          throw new RuntimeException('查询节点参数错误：未知的CLASS定义（'.$str.'）');
        }
        $classname = explode('.',$substr);
      } else {
        $tag = $str;
        if (!in_array($tag, $this->allow_tags)) {
          throw new RuntimeException('查询节点参数错误：未知的标签（'.$tag.'）');
        }
      }
      $xpath[] = compact('tag', 'identity', 'classname');
    }
    return $xpath;
  }
  
  /**
   * 根据节点的起始html获得节点内容
   * @access protected
   * @param string $html
   * @param integer $n
   * @return string
   */
  protected function getNodeHtml($html, $n = 0) {
    $tag = trim(substr($html,1,strpos($html, ' ')));
    if (!in_array($tag, $this->allow_tags)) {
      return '';
    }
    $start = strpos($this->html, $html, $n);
    if ($tag == 'img' || $tag == 'input') {
      $end = strpos($this->html, '>', $start);
      ($end !== false) && ($end += strlen('>'));
    } else {
      $n = 1;
      $point = $start + strlen('<'.$tag);
      while ($n > 0 && false !== ($end = strpos($this->html, '/'.$tag.'>', $point))) {
        $end += strlen('/'.$tag.'>');
        $n += substr_count(substr($this->html, $point, $end - $point), '<'.$tag) - 1;
        $point = $end;
      }
    }
    return $end === false ? '' : substr($this->html, $start, $end - $start);
  }
  
  /**
   * 查询并返回节点列表
   * @access public
   * @param string $path
   * @return \Illuminate\Support\Collection|null
   */
  public function find($path) {
    $collect = collect();
    foreach ($this->getXPath($path) as $xpath) {
      extract($xpath);
      $pattern = '<';
      $pattern .= $tag ? $tag.'[^>]*' : '[^>]+';
      $pattern .= $identity ? '[^>]*\s+id\s*=[\'|"]'.$identity.'[\'|"][^>]*' : '';
      $pattern .= '>';
      if (!preg_match_all('/'.$pattern.'/i', $this->html, $matches)) {
        continue;
      }
      
      $nodes = [];
      foreach ($matches[0] as $match) {
        $hit = true;
        if ($classname) {
          if (!preg_match('/class\s*=[\'|"](.+?)[\'|"]/i', $match, $styles)) {
            continue;
          }
          $styles = explode('$$$', preg_replace('/\s+/i', '$$$', strtolower($styles[1])));
          foreach ($classname as $cls) {
            if (!in_array(strtolower($cls), $styles)) { $hit = false; break; }
          }
        }
        $hit && $nodes[] = $match;
      }
      $n = 0;
      foreach ($nodes as $node) {
        if ($n === false) { break; }
        $html = $this->getNodeHtml($node, strpos($this->html, $node, $n));
        $n = strpos($this->html, $node, $n) + strlen($node);
        if (!$html) { continue; }
        $collect->push(new Document($html));
      }
    }
    return $collect;
  }
  
  /**
   * 判断是否存在对应路径
   * @access public
   * @param string $path
   * @return bool
   */
  public function has($path) {
    $collect = $this->find($path);
    return !$collect->isEmpty();
  }
  
  /**
   * 获得节点属性值列表
   * @access public
   * @return array
   */
  public function attrs() {
    if (!$this->html) {
      return [];
    }
    if (!$this->attributes) {
      $html = substr($this->html,0,strpos($this->html,'>') + 1);
      if (preg_match_all('/([a-z\-_]+)\s*="(.+?)"/i', $html, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
          $k = trim($matches[1][$i]);
          $v = trim($matches[2][$i]);
          $this->attributes[$k] = $v;
        }
      }
      if (preg_match_all('/([a-z\-_]+)\s*=(.+?) /i', $html, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
          $k = trim($matches[1][$i]);
          $v = trim($matches[2][$i]);
          $v = trim($v, '"');
          $v = trim($v, "'");
          !isset($this->attributes[$k]) && $this->attributes[$k] = $v;
        }
      }
    }
    return $this->attributes;
  }
  
  /**
   * 根据名称获得节点属性
   * @access public
   * @param string $name
   * @return string
   */
  public function attr($name) {
    $attrs = $this->attrs();
    return isset($attrs[$name]) ? $attrs[$name] : '';
  }
  
  /**
   * 根据名称获得节点自定义属性
   * @access public
   * @param string $name
   * @return string
   */
  public function data($name) {
    return $this->attr('data-'.$name);
  }
  
  /**
   * 获得节点的内容HTML
   * @access public
   * @return string
   */
  public function html() {
    $tag = trim(substr($this->html,1,strpos($this->html, ' ')));
    if (!in_array($tag, $this->allow_tags) || $tag == 'img' || $tag == 'input') {
      return '';
    }
    $start = strpos($this->html, '>') + 1;
    $end = strrpos($this->html, '</');
    return trim(substr($this->html, $start, $end - $start));
  }
  
}
