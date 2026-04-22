<?php
// app/middleware/JwtMiddleware.php

namespace app\middleware;

use app\modules\myclass\JwtService;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class JwtMiddleware implements MiddlewareInterface
{
    protected $jwtService;

    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    public function process(Request $request, callable $handler): Response
    {
        // 跳过OPTIONS预检请求
        if ($request->method() === 'OPTIONS') {
            return $handler($request);
        }
        $token = $this->jwtService->getTokenFromHeader($request);

        if (!$token) {
            debugMessage([$request->header('authorization'),$request->route->getName()], 'token_error 无效的Token');
            return error('token_error', token_error);
        }

        $validation = $this->jwtService->validateToken($token, $request);
        $currentRouteName = $request->route->getName();

        if (!$validation['success']) {
            $code = $validation['code'];
            $message = $validation['message'];
            debugMessage([$validation,$request->route->getName()], 'validation');
            return error($message, $code);
        }
        // 将用户信息添加到请求属性中，方便后续使用
        $request->jwtPayload = $validation['data'];
        $request->jwtUserId = $validation['user_id'];
        $user = (array)$validation['data']['user'];
        $request->jwtUserType = $user['user_type'] ?? '';
        $request->jwtProvider = $user['provider'] ?? '';

        try {

            $response =  $handler($request);
        } catch (\Exception $e) {
            return error($e->getMessage(), $e->getCode());
        }

        $token =  $this->jwtService->generateToken((array)$validation['data']['user'], $validation['data']['ip']);
        $currentRouteName = $request->route->getName();
        $raw_body = $response->rawBody();
        $data = json_decode($raw_body, true);
        if (is_array($data)) {
            // 将新 token 插入响应数据
            $data['token'] = $token;
            if (!empty($currentRouteName) && stripos($currentRouteName, 'index') !== false) {
                $data['data'] = array_merge($data['data'], ['page' => $request->page, 'limit' => $request->limit, 'offset' => $request->offset]);
            }
            // 重新设置 JSON 响应
            $response->withBody(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $response;
    }
}
