<?php

namespace app\modules\user\userInfo\controllers;

use support\Response;
use app\core\Foundation\FeatureRegistry;
use app\modules\user\userInfo\fns\UserinfoFn;
use support\Request;

class UserUserinfoController
{
    public function index(Request $request, UserinfoFn $userinfoFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        $all['jwtUserType'] = $request->jwtUserType;
        if (!in_array($request->jwtUserType, [admin_str])) tryFun('user_type_rule', user_type_rule);
        $list = $userinfoFn->index($all, $request->limit, $request->offset);
        return success($list);
    }
    public function getUserIdToken(Request $request, UserinfoFn $userinfoFn)
    {

        $all['jwtUserType'] = $request->jwtUserType;
        if (!in_array($request->jwtUserType, [admin_str])) tryFun('user_type_rule', user_type_rule);
        $account_number = $request->input('account_number');
        $provider = $request->input('provider');
        $ip = $request->getRealIp();
        $info = $userinfoFn->getUserIdToken($account_number, $provider, $ip);
        return success($info);
    }
    public function setPicture(Request $request, UserinfoFn $userinfoFn)
    {
        $picture  = $request->input('picture');
        $id = $request->jwtUserId;

        return success($userinfoFn->setPicture($id, $picture));
    }
    public function upUserInfo(Request $request, UserinfoFn $userinfoFn)
    {
        $data = [
            'last_name' => $request->input('last_name') ?? '',
            'first_name' => $request->input('first_name') ?? '',
            'phone' => $request->input('phone') ?? '',
            'password' => $request->input('password') ?? '',
            'password2' => $request->input('password2') ?? '',
        ];
        $id = $request->jwtUserId;

        $userinfoFn->upUserInfo($id, $data);
        return success();
    }
    public function getUserInfo(Request $request, UserInfoFn $userinfoFn)
    {
        return success($userinfoFn->getUidInfo($request->jwtUserId,$request->jwtProvider));
    }
    public function updateEmail(Request $request, UserInfoFn $userinfoFn)
    {
        $sms_code = $request->input('sms_code');
        $account_number = $request->input('account_number');
        $uid = $request->jwtUserId;
        $is =  $userinfoFn->updateEmail($uid, $account_number, $sms_code);
        return success($is);
    }
    public function test(Request $request): Response
    {
        exec('php start.php reload');
        // 统计 Workerman 启动了多少个 worker 进程（按配置）
        $total_workers = 0;
        $workers_detail = [];
        if (class_exists(\Workerman\Worker::class)) {
            foreach (\Workerman\Worker::getAllWorkers() as $w) {
                // $w->count 为该 worker 配置的进程数
                $total_workers += (int) $w->count;
                $workers_detail[] = [
                    'name'   => $w->name ?? null,
                    'count'  => (int) $w->count,
                    'listen' => method_exists($w, 'getSocketName') ? $w->getSocketName() : null,
                ];
            }
        }

        // Webman 的 http worker 数（如果你在 config/server.php 里配置了 count）
        $http_count = null;
        if (function_exists('config')) {
            $http_count = config('server.count') ?? null;
        }
        $realIp   = $request->getRealIp();  // ✅ 真实客户端 IP（经反代后）
        $remoteIp = $request->getRemoteIp(); // 直接连接到 Webman 的来源 IP（通常是 Nginx）
        return success([
            'realIp' => $realIp,
            'remoteIp' => $remoteIp,
            'pid'            => getmypid(),                    // 当前处理该请求的进程 PID
            'feature_count'  => count(FeatureRegistry::names()),
            'features'       => FeatureRegistry::names(),
            'http_count'     => $http_count,                  // HTTP worker 配置数量（如有）
            'total_workers'  => $total_workers,               // 所有 worker（含其它自定义进程）的总数（按配置）
            'workers_detail' => $workers_detail,              // 每个 worker 的名称/数量/监听信息
        ]);
    }
    public function show(Request $request, UserinfoFn $userinfoFn)
    {
        if (!in_array($request->jwtUserType, [admin_str])) tryFun('user_type_rule', user_type_rule);
        $id = $request->input('id');
        $info = $userinfoFn->show($id);
        return success($info);
    }
    public function createBasic(Request $request, UserinfoFn $userinfoFn)
    {
        if (!in_array($request->jwtUserType, [admin_str])) tryFun('user_type_rule', user_type_rule);
        $all = $request->all();
        $uid = $request->input('id') ?? 0;
        if ($uid > 0) {
            $uid =  $userinfoFn->updateBasic($uid, $all);
        } else {
            $uid =  $userinfoFn->createBasic($all);
        }
        return success($uid);
    }
    public function createAccount(Request $request, UserinfoFn $userinfoFn)
    {
        if (!in_array($request->jwtUserType, [admin_str])) tryFun('user_type_rule', user_type_rule);
        $all = $request->all();
        $id = $request->input('id') ?? 0;
        if ($id > 0) {
            $userinfoFn->updateAccount($id, $all);
        } else {
            $userinfoFn->createAccount($all);
        }
        return success();
    }
}
