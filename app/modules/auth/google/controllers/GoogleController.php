<?php

namespace app\modules\auth\google\controllers;

use app\modules\auth\google\fns\GoogleFn;
use support\Request;
use support\Response;

class GoogleController
{
    public function googleAuthUrl(Request $request): Response
    {
        $state = $request->input('state');
        $url = feature('myclass.GoogleAuthService.getAuthUrl', $state);
        return success($url);
    }
    /**
     * Google登录回调注册/登录
     * @param Request $request
     */
    public function authGoogleCallback(Request $request, GoogleFn $googleFn)
    {
        $code = $request->input('code');
        if (empty($code)) {
            return error('authorization_code_not', authorization_code_not);
        }
        try {
            $userInfo = feature('myclass.GoogleAuthService.getUserInfo', $code);
            $ip = $request->getRealIp();
            $info = $googleFn->registerFun($userInfo, $ip);
            return success($info, 'ok', 200, $info['token']);
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), '谷歌登录');
            return error('google_login_err', google_login_err);
        }
    }
    public function bind(Request $request, GoogleFn $googleFn)
    {
        $code = $request->input('code');
        $uid = $request->jwtUserId;
        if (empty($code)) {
            return error('authorization_code_not', authorization_code_not);
        }
        try {
            $userInfo = feature('myclass.GoogleAuthService.getUserInfo', $code);

            $googleFn->bind($userInfo, $uid);
            return success();
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), '谷歌绑定');
            return error('google_bind_err', google_bind_err);
        }
    }
}
