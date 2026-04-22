<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Request;
use Webman\Http\Response;

class PageMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $page  = intval($request->input('page', 1));
        $limit = intval($request->input('limit', 20));
        $page < 1 && $page = 1;
        // ($limit < 1 || $limit > 5000) && $limit = 20;
        $request->offset = ($page - 1) * $limit;
        $request->limit =$limit;
        $request->page =$page;
        $response = $next($request);
        return $response;
    }
}
