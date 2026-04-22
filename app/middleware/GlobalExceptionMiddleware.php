<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Tinywan\ExceptionHandler\Exception\BadRequestHttpException;

class GlobalExceptionMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        try {
            return $handler($request);
        } catch (BadRequestHttpException $e) {
            return error($e->getMessage(), $e->getCode());
            // ② 其它异常统一兜底（你原来的逻辑）
        } catch (\Throwable $e) {
            // 这里可按不同异常类型/代码映射
            $msg = $e->getMessage() ?: 'server error';
            return error($msg, 500000, ['exception' => class_basename($e)]);
        }
    }
}
