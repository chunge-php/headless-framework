<?php

namespace app\modules\auth\email\controllers;

use app\modules\auth\email\fns\EmailFn;
use support\Request;

class EmailController
{
    /**
     * 注册邮件并校验验证码
     * @param \support\Request $request
     * @return \support\Response
     */
    public function sendRegister(Request $request, EmailFn $emailFn)
    {
        try {
            $ip = $request->getRealIp();
            $encrypted = $request->input('encrypted') ?? '';
            $iv = $request->input('iv') ?? '';
            $info = AESDeCode($encrypted, $iv);
            $emailFn->sendRegister($info, $ip);
            return success();
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), '注册邮件并校验');
            return error($e->getMessage(), info_err);
        }
    }
    public function bind(Request $request, EmailFn $emailFn)
    {
        $sms_code = $request->input('sms_code') ?? '';
        $account_number = $request->input('account_number') ?? '';
        $uid = $request->jwtUserId;
        $provider = $request->input('provider');
        try {
            $is =  $emailFn->bind($uid, $sms_code, $account_number, $provider);
            return success($is);
        } catch (\Exception $e) {
            return error($e->getMessage(), info_err);
        }
    }
}
