<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class LangMiddleware implements MiddlewareInterface
{
    // 静态变量存储语言信息
    public function process(Request $request, callable $next): Response
    {
      
        $default_timezone = $request->header('default_timezone');
        if (!empty($default_timezone)) {
            date_default_timezone_set($default_timezone);
        } else {
            $originalTimezone = config('app.default_timezone');
            date_default_timezone_set($originalTimezone);
        }
      try{
        $lang =  $request->header('Accept-Language') ?? 'zh-CN';
        locale($lang);
        $response = $next($request);
        return $response;
      }catch(\Exception $e){
        $response = $next($request);
        return $response;
      }
    }
}
