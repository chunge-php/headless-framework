<?php
namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use app\modules\myclass\ApiAuth;

class ApiAuthMiddleware
{
    public function process(Request $request, callable $next): Response
    {
        $params = $request->all();

        $verify = ApiAuth::verifyAndDecrypt($params);
        if (!$verify['success']) {
            return error($verify['msg']);
        }

        // 把解密后的业务数据给 Controller
        $request->data = $verify['data'];
        $request->uid = $verify['uid'];

        return $next($request);
    }
}
