<?php namespace YitOS\WebSocket\Middleware;

use Closure;
use Illuminate\Http\Request;

use YitOS\Support\Facades\WebSocket;

/**
 * 检查是否已为客户端分配令牌
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\WebSocket\Middleware
 */
class CheckForClientToken {
  
  /**
   * 如果client_token会话不存在则通过API检查客户端IP并获得令牌
   * 否则抛出403错误
   * @param Request $request
   * @param Closure $next
   */
  public function handle(Request $request, Closure $next) {
    if (!WebSocket::tokenExists()) {
      abort(403, trans('websocket::exception.access_denied'));
    }
    return $next($request);
  }
  
}
