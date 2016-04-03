<?php namespace YitOS\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use YitOS\Support\Facades\WebSocket;

/**
 * 检查是否已为客户端分配令牌
 *
 * @author yiller <tech.yiller@yitos.cn>
 * @package YitOS\Foundation\Http\Middleware
 */
class CheckForClientToken {
  
  /**
   * 如果client_token会话不存在则通过API检查客户端IP并获得令牌
   * 否则抛出403错误
   * 
   * @param Request $request
   * @param Closure $next
   */
  public function handle(Request $request, Closure $next) {
    if (!WebSocket::refreshToken()) {
      throw new HttpException(403);
    }
    return $next($request);
  }
  
  //put your code here
}
