<?php namespace YitOS\Contracts\WebSocket;

/**
 * 支持第三方远程同步的数据模型接口
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Contracts\WebSocket
 */
interface ExternalSyncModel {
  
  /**
   * 是否支持远端同步
   * @access public
   * @return bool
   */
  public function isExternal();
  
  /**
   * 获得第三方远端源
   * @access public
   * @return string
   */
  public function getExternalSource();
  
  /**
   * 获得第三方远端标识
   * @access public
   * @return string
   */
  public function getExternalId();
  
}
