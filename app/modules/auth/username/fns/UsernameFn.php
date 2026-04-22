<?php

namespace app\modules\auth\username\fns;

use app\modules\user\userInfo\model\UserIdentitie;

class UsernameFn
{

    protected $userIdentitie;
    protected $jwtService;
    public function __construct(UserIdentitie $userIdentitie)
    {
        $this->userIdentitie = $userIdentitie;
    }
    public function unifyLogin($account_number, $pwd = null, $ip = null)
    {
        $user_info =    feature('user.userInfo.getUserInfo', $account_number);
        $info = null;
        if ($user_info) {
            if ($user_info['state'] == state_zero) {
                return error('user_state_zero', user_state_zero);
            }
            if ($user_info['status'] == state_zero) {
                return error('user_status_zero', user_status_zero);
            }
            switch ($user_info['provider']) {
                case 'username':
                    if (empty($pwd)) return error('user_pwd_empty', user_pwd_empty);
                    $is =   feature('user.userInfo.getSecretHash', $user_info['uid'], $pwd);
                    if (!$is) return  error('user_pwd_not', user_pwd_not);
                    $info = feature('user.userInfo.getUserInfoToken', $user_info, $ip);
                    break;
                case 'email':
                    if (empty($pwd)) return error('user_pwd_empty', user_pwd_empty);
                    $is =   feature('user.userInfo.getSecretHash', $user_info['uid'], $pwd);
                    if (!$is) return  error('user_pwd_not', user_pwd_not);
                    $info = feature('user.userInfo.getUserInfoToken', $user_info, $ip);
                    break;
                default:
                    return error('user_provider_not', user_provider_not);
            }
            $this->userIdentitie->where('uid', $user_info['uid'])->update(['linked_at' => dayDateTime()]);
            return success($info,'ok',200,$info['token']);
        }
        return error('user_info_not', user_info_not);
    }
}
