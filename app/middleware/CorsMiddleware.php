<?php
// app/middleware/CorsMiddleware.php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 如果是 OPTIONS 请求则返回一个空响应
        if ($request->method() == 'OPTIONS') {
            $response = response('', 200);
        } else {
            $response = $handler($request);
        }
        
        // 添加 CORS 头
        $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type,Accept-Language,Authorization,X-Requested-With,Accept,Origin,X-CSRF-TOKEN',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => 86400,
        ]);
        
        return $response;
    }
}