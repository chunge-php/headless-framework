<?php

namespace app\modules\auth\email\fns;

use support\Db;

class EmailFn
{
    public function sendRegister($userInfo, $ip = '', $channel = '')
    {
        if ($channel != admin_str) {
            if (empty($userInfo['sms_code'])) {
                tryFun('sms_verify_err', sms_verify_err);
            }
            $is = feature('sms.register.verifyCode', $userInfo['account_number'], $userInfo['sms_code']);
            if (!$is) {
                tryFun('sms_verify_err', sms_verify_err);
            }
        }
        Db::beginTransaction();
        $user_info =    feature('user.userInfo.getUserInfo', $userInfo['account_number'], email_str);

        if (!$user_info) {
            //创建用户
            $uid = feature('user.userInfo.create', $userInfo);
            //创建身份
            $identity_id = feature('user.userInfo.identitiesCreate', ['uid' => $uid, 'provider' => email_str, 'account_number' => $userInfo['account_number'], 'verified_at' => dayDateTime(), 'linked_at' => dayDateTime()]);
            feature('user.userInfo.credentialCreate', ['identity_id' => $identity_id, 'secret_hash' => getPasswordHash($userInfo['password'])]);
            //创建钱包
            feature('user.wallet.create', ['uid' => $uid]);
            $user_info =    feature('user.userInfo.getUserInfo', $userInfo['account_number'], email_str);
        } else {
            if ($channel != admin_str) {
                Db::rollBack();
                tryFun('email_exists', email_exists);
            }
        }
        Db::commit();
        return feature('user.userInfo.getUserInfoToken', $user_info, $ip);
    }
    public function bind($uid, $sms_code, $account_number, $provider)
    {
        if (empty($sms_code)) {
            tryFun('sms_verify_err', sms_verify_err);
        }
        $is = feature('sms.register.verifyCode', $account_number, $sms_code);
        if (!$is) {
            tryFun('sms_verify_err', sms_verify_err);
        }
        if (!in_array($provider, [email_str, google_str, sms_str])) tryFun('provider_error', user_provider_not);
        $user_info = feature('user.userInfo.getUserInfo', $account_number, $provider);
        if (!$user_info) {
            feature('user.userInfo.identitiesCreate', ['uid' => $uid, 'provider' => $provider, 'account_number' => $account_number, 'verified_at' => dayDateTime(), 'linked_at' => dayDateTime()]);
            return true;
        } else {
            if ($user_info['uid'] != $uid) {
                tryFun('google_bind_not', google_bind_not);
            }
        }
        return true;
    }
}
