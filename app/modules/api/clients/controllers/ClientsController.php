<?php

namespace app\modules\api\clients\controllers;

use app\modules\api\clients\fns\ClientsFn;
use support\Request;

class ClientsController
{

    public function show(Request $request, ClientsFn $ClientsFn)
    {
        $all = $request->all();
        return success($ClientsFn->show($request->jwtUserId));
    }
    public function status(Request $request, ClientsFn $ClientsFn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($ClientsFn->status($all));
    }

    public function sendSms(Request $request, ClientsFn $ClientsFn)
    {

        $all = $request->data;
        $all['uid'] = $request->uid;
        $ClientsFn->sendSms($all);
        return success();
    }
    public function getBalance(Request $request, ClientsFn $ClientsFn)
    {
        $uid = $request->uid;
        return success($ClientsFn->getBalance($uid));
    }
    public function createSmsAccount(Request $request, ClientsFn $ClientsFn)
    {
        $all = $request->data;
        $data = $ClientsFn->createSmsAccount($all);
        return success($data);
    }
    public function getSmsToken(Request $request, ClientsFn $ClientsFn)
    {
        $all = $request->data;
        if(empty($all['tables'])){
            return error('tables不能为空', 400);
        }
        $ip = $request->getRealIp();
        $token = $ClientsFn->getSmsToken($all['tables']??'',$ip);
        return  success($token);
    }
}
