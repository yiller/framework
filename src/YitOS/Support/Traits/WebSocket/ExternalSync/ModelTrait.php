<?php namespace YitOS\Support\Traits\WebSocket\ExternalSync;

/**
 * 支持第三方远程同步的数据模型分离类
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Support\Traits\WebSocket\ExternalSync
 * @see \YitOS\Contracts\WebSocket\ExternalSync\Model
 */
trait ModelTrait {
  
  /**
   * 是否支持远端同步
   * @access public
   * @return bool
   */
  public function isExternal() {
    return isset($this->external['enabled']) && $this->external['enabled'];
  }
  
  /**
   * 获得第三方远端源
   * @access public
   * @return string
   */
  public function getExternalSource() {
    if ($this->isExternal() && isset($this->external['source'])) {
      return $this->external['source'];
    }
    return '';
  }
  
  /**
   * 获得第三方远端标识
   * @access public
   * @return string
   */
  public function getExternalId() {
    if ($this->isExternal() && isset($this->external['id'])) {
      return $this->external['id'];
    }
    return '';
  }
  
  /**
   * 获得第三方远端来源地址
   * @access public
   * @return string
   */
  public function getExternalUrl() {
    if ($this->isExternal() && isset($this->external['url'])) {
      return $this->external['url'];
    }
    return '';
  }
  
}
