<?php

namespace app\modules\sms\register\controllers;

use app\modules\sms\register\fns\SmsFn;
use support\Request;

class SmsController
{
    public function send(Request $request, SmsFn $smsFn)
    {

        $encrypted = $request->input('encrypted') ?? '';
        $iv = $request->input('iv') ?? '';
        $info = AESDeCode($encrypted, $iv);
        $account_number = $info['account_number'] ?? '';
        $auto_type = $info['auto_type'] ?? -1;
        $template_type = $info['template_type'] ?? 0;
        $password = $info['password'] ?? '';
        $name = $info['name'] ?? '';
        $res = $smsFn->send($account_number, $auto_type, $template_type,$password,$name);
        if (!empty($res['code'])) {
            return success();
        } else {
            return error($res['msg'], $res['status']);
        }
    }
    public function verifyCode(Request $request, SmsFn $smsFn)
    {
        $encrypted = $request->input('encrypted') ?? '';
        $iv = $request->input('iv') ?? '';
        $info = AESDeCode($encrypted, $iv);
        $account_number = $info['account_number'] ?? '';
        $sms_code = $info['sms_code'] ?? '';
        $is =  $smsFn->verifyCode($account_number, $sms_code);
        if ($is) {
            return success();
        } else {
            return error('验证码错误', info_err);
        }
    }
}
