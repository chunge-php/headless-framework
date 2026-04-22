<?php

namespace app\modules\api\clients\fns;

use app\modules\api\clients\model\ApiClients;
use app\modules\batchLog\model\BatchLog;
use app\modules\myclass\ApiAuth;
use app\modules\user\userInfo\model\User;

class  ClientsFn
{
    protected $model;
    protected $apiAuth;
    protected $apiClients;

    protected $user;
    protected $batchLog;
    public function __construct(ApiClients $model, ApiAuth $apiAuth, ApiClients $apiClients, User $user, BatchLog $batchLog)
    {
        $this->model = $model;
        $this->batchLog = $batchLog;
        $this->apiAuth = $apiAuth;
        $this->apiClients = $apiClients;
        $this->user = $user;
    }


    public function show($uid)
    {
        $api_url = 'https://docs.apipost.net/docs/detail/5667ef085888000?target_id=267239d1f415ba';
        $api_data =  $this->apiClients->select(['access_key', 'secret_key', 'status', 'created_at'])->where('uid', $uid)->first()?->toArray();
        if (!empty($api_data)) {
            $api_data['api_url'] = $api_url;
            return $api_data;
        }
        $data =  $this->apiAuth->createClient($uid);
        $data['api_url'] = $api_url;
        $data['status'] = state_one;
        return $data;
    }
    public function getSmsToken($tables, $ip)
    {
        $account_number = $this->user->where('users.tables', $tables)->join('user_identities', 'user_identities.uid', '=', 'users.id')->where('user_identities.provider', 'username')->value('user_identities.account_number');
        $user_info =    feature('user.userInfo.getUserInfo', $account_number);
        $res =   feature('user.userInfo.getUserInfoToken', $user_info, $ip);
        if ($res) {
            $res['batch_log'] =  $this->batchLog->where('uid', $res['id'])->where('scope', 'member_import')->first()?->toArray();
            if($res['batch_log']){
                $res['batch_log']['invalid_phones'] = json_decode($res['batch_log']['invalid_phones'], true);   
            }
            $res['api_key']= $this->show($res['id']);
        }
        return $res;
    }
    public function getBalance($uid)
    {
        return feature('user.wallet.getBalance', $uid);
    }
    public function status($info)
    {
        return $this->model->where('uid', $info['jwtUserId'])->update(['status' => $info['status']]);
    }
    public function sendSms($info)
    {
        $price_id =   feature('sms.config.getPriceId', $info['send_type']);
        $data = [
            "price_id" => $price_id,
            "content" => $info['body'],
            "account_number" => $info['account_number'],
            'group_id' => [],
            'tags_id' =>  [],
            'mms_url' => $info['mms_url'] ?? '',
            'lessee_name' => $info['lessee_name'] ?? '',
            'lessee_code' => $info['lessee_code'] ?? '',
            'channel_type' => isset($info['channel_type']) ? $info['channel_type'] : state_two,
            'subject' => $info['subject'] ?? '',
            'jwtUserId' => $info['uid'],
            'name' =>  'Api~',
            'scope_id' => 0,
            'scope' => dev_str,
            'subscribe_type' => state_zero
        ];
        return  feature('sms.body.create', $data);
    }
    public function createSmsAccount($info)
    {

        if (isset($info['data']) && isset($info['user_info'])) {
            $param = array_merge($info['data'], $info['user_info']);
            unset($param['id'], $param['uid'], $param['created_at'], $param['updated_at'], $param['user_identitie'], $param['extend_json'], $param['device']);
            $data =   feature('auth.email.sendRegister', $param, '', admin_str);
            return  feature('clients.show', $data['id']);
        } else {
            tryFun('info_err', info_err);
        }
    }
}
