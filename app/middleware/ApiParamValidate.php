<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use think\Validate;
use think\exception\ValidateException;
use Webman\MiddlewareInterface;

/**
 * 专用于加密 API 的参数验证
 * 用 `$request->data` 进行校验！！
 */
class ApiParamValidate implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 仅对“控制器形式”的路由生效（闭包路由跳过）
        $controllerClass = $request->controller ?? null;
        $action          = $request->action ?? null;
        if (!$controllerClass || !$action) {
            return $handler($request);
        }

        // 1) 控制器 -> 验证器类名映射
        $validateClass = $this->inferValidateClass($controllerClass);

        if (!class_exists($validateClass)) {
            // 验证器不存在 → 跳过
            return $handler($request);
        }

        // 2) 生成场景名：METHOD_action
        $scene = strtoupper($request->method()) . '_' . $action;

        /** @var Validate $validator */
        $validator = new $validateClass();

        // 3) 验证器无该场景 → 跳过
        $scenes = $this->getScenes($validator);
        if (!in_array($scene, array_keys($scenes), true)) {
            return $handler($request);
        }

        // 4) 执行校验
        try {
            $params = $request->data ?? $request->all();

            if (!$validator->scene($scene)->check($params)) {
                // think-validate 一般抛异常；但以防万一保底
                return error($validator->getError(), info_err);
            }
        } catch (ValidateException $e) {
            return $this->error($e->getError(), info_err);
        } catch (\Throwable $e) {
            // 其他异常统一 400

            return $this->error($e->getMessage(), info_err);
        }

        // 5) 通过
        return $handler($request);
    }

    /**
     * 将控制器类名映射到验证器类名：
     * app\modules\X\http\Controllers\FooController -> app\modules\X\http\Validate\FooValidate
     */
    private function inferValidateClass(string $controllerClass): string
    {
        // 替换命名空间片段 Controllers -> Validate
        $validateClass = str_replace('\\controllers\\', '\\validate\\', $controllerClass);
        $validateClass = str_replace('\\validate\\', '\\validate\\Api', $validateClass);
        // 替换类名后缀 Controller -> Validate
        $validateClass = preg_replace('/Controller$/', 'Validate', $validateClass) ?? $validateClass;
        return $validateClass;
    }

    /**
     * 读取验证器的场景数组（think-validate 支持 protected $scene）
     * @return array<string, array>
     */
    private function getScenes(\think\Validate $validator): array
    {
        try {
            $ref = new \ReflectionClass($validator);

            // 逐级向上找 $scene（支持父类里定义）
            while ($ref) {
                if ($ref->hasProperty('scene')) {
                    $prop = $ref->getProperty('scene');
                    // 仅当是数组类型时再读取
                    if ($prop->isPublic()) {
                        $val = $prop->getValue($validator);
                        return is_array($val) ? $val : [];
                    }
                    // 非 public 用反射读取
                    $prop->setAccessible(true);
                    $val = $prop->getValue($validator);
                    return is_array($val) ? $val : [];
                }
                $ref = $ref->getParentClass();
            }
        } catch (\Throwable $e) {
            // 忽略，返回空
        }
        // 没有 scene 时返回空数组即可（表示跳过验证）
        return [];
    }

    private function error(string $message, int $status = 400): Response
    {
        return json([
            'code'    => $status,
            'message' => $message,
            'data'    => null,
        ], $status);
    }
}
