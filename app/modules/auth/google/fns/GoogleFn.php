<?php

namespace app\modules\auth\google\fns;

use support\Db;

class GoogleFn
{
    public function registerFun($userInfo, $ip)
    {
        Db::beginTransaction();
        try {
            $user_info =    feature('user.userInfo.getUserInfo', $userInfo['email'], google_str);
            if (!$user_info) {
                //创建用户
                $uid = feature('user.userInfo.create', $userInfo);

                //创建身份
                $identity_id = feature('user.userInfo.identitiesCreate', ['uid' => $uid, 'provider' => google_str, 'account_number' => $userInfo['email'], 'verified_at' => dayDateTime(), 'linked_at' => dayDateTime(), 'meta_json' => json_encode($userInfo)]);
                //创建钱包
                feature('user.wallet.create', ['uid' => $uid]);
                $user_info =    feature('user.userInfo.getUserInfo', $userInfo['email'], google_str);
            } else {
                //更新登录时间
                feature('user.userInfo.identitiesUpdate', $userInfo['email'], google_str, ['linked_at' => dayDateTime()]);
            }
            Db::commit();
            return feature('user.userInfo.getUserInfoToken', $user_info, $ip);
        } catch (\Exception $e) {
            Db::rollBack();
            tryFun($e->getMessage(), $e->getCode() ?: info_err);
        }
    }
    public function bind($userInfo, $uid)
    {
        $user_info =    feature('user.userInfo.getUserInfo', $userInfo['email'], google_str);
        if (!$user_info) {
            //创建用户
            feature('user.userInfo.identitiesCreate', ['uid' => $uid, 'provider' => google_str, 'account_number' => $userInfo['email'], 'verified_at' => dayDateTime(), 'linked_at' => dayDateTime(), 'meta_json' => json_encode($userInfo)]);
            return true;
        } else {
            if ($user_info['uid'] != $uid) {
                tryFun('google_bind_not', google_bind_not);
            } else {
                feature('user.userInfo.identitiesUpdate', $userInfo['email'], google_str, ['meta_json' => json_encode($userInfo)]);
                return true;
            }
        }
        return false;
    }
}
