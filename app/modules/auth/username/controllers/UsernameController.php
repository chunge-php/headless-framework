<?php

namespace app\modules\auth\username\controllers;

use support\Request;
use support\Response;

class UsernameController
{
    public function test(): Response
    {
        return success();
    }
    /**
     * 统一登录
     * @param \support\Request $request
     * @return void
     */
    public function unifyLogin(Request $request)
    {
        $ip = $request->getRealIp();
        $encrypted = $request->input('encrypted') ?? '';
        $iv = $request->input('iv') ?? '';
        $data = AESDeCode($encrypted, $iv);
        $username = $data['account_number'] ?? '';
        $password = $data['password'] ?? '';
        return  feature('auth.username.unifyLogin', $username, $password, $ip);
    }
}
