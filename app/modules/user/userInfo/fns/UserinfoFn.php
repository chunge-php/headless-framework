<?php

namespace app\modules\user\userInfo\fns;

use app\modules\myclass\JwtService;
use app\modules\user\userInfo\model\User;
use app\modules\user\userInfo\model\UserCredential;
use app\modules\user\userInfo\model\UserIdentitie;
use support\Db;

class UserinfoFn
{
    private $user;
    private $userIdentitie;
    private $jwtService;
    private $userCredential;
    public function __construct(User $user, UserIdentitie $userIdentitie, JwtService $jwtService, UserCredential $userCredential)
    {
        $this->user = $user;
        $this->userIdentitie = $userIdentitie;
        $this->jwtService = $jwtService;
        $this->userCredential = $userCredential;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->user
            ->select([
                'users.id',
                'users.code',
                'users.last_name',
                'users.first_name',
                'users.phone',
                'users.picture',
                'wallets.account_sid',
                'wallets.balance',
                'user_identities.account_number',
                'user_identities.status',
                'user_identities.provider',
                'user_identities.verified_at',
                'user_identities.linked_at',
                'user_identities.created_at',
            ])
            ->join('user_identities', 'users.id', '=', 'user_identities.uid')
            ->join('wallets', 'users.id', '=', 'wallets.uid')
            ->whereIn('user_identities.user_type', [user_str, dev_str])
            ->when(!empty($info['provider']), fn($query) => $query->where('user_identities.provider', $info['provider']))
            ->when(!empty($info['seek']), function ($query) use ($info) {
                return $query->where(function ($query) use ($info) {
                    $seek = $info['seek'];
                    return $query->where('users.last_name', 'like', "%{$seek}%")
                        ->orWhere('users.first_name', 'like', "%{$seek}%")
                        ->orWhere('users.phone', 'like', "%{$seek}%")
                        ->orWhere('user_identities.account_number', 'like', "%{$seek}%")
                    ;
                });
            });
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('users.id', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
    public function setPicture($id, $picture)
    {
        return $this->user->where('id', $id)->update(['picture' => $picture]);
    }
    public function upUserInfo($id, $userInfo)
    {
        $this->user->where('id', $id)->update(filterFields($userInfo, $this->user));
        if (!empty($userInfo['password']) && !empty($userInfo['password2'])) {
            $identity_id =  $this->userIdentitie->where('uid', $id)->pluck('id')->toArray();
            $hash = $this->userCredential->whereIn('identity_id', $identity_id)->pluck('secret_hash')->toArray();
            if (empty($hash)) {
                $this->userCredential->insert(['identity_id' => $identity_id[0], 'secret_hash' => getPasswordHash($userInfo['password'])]);
            } else {
                $is = false;
                foreach ($hash as $val) {
                    $is =  verifyPassword($userInfo['password2'],  $val);
                    if ($is) break;
                }
                if (!$is) tryFun('user_pwd_not', user_pwd_not);
                if ($identity_id) {
                    $this->userCredential->where('identity_id', $identity_id[0])->update(['secret_hash' => getPasswordHash($userInfo['password'])]);
                }
            }
        }
    }
    public function getUserInfo($account_number, $provider = '')
    {
        return    $this->userIdentitie
            ->select(['user_identities.*', 'users.code', 'users.last_name', 'users.first_name', 'users.phone', 'users.picture', 'users.state'])
            ->join('users', 'users.id', 'user_identities.uid')
            ->where('user_identities.account_number', $account_number)
            ->when(!empty($provider), fn($query) => $query->where('user_identities.provider', $provider))
            ->first()?->toArray();
    }
    public function getUidInfo($uid, $provider)
    {
        $data =    $this->userIdentitie
            ->select(['user_identities.*', 'users.code',  'users.last_name', 'users.first_name', 'users.phone', 'users.picture', 'users.state', 'users.extend_json'])
            ->join('users', 'users.id', 'user_identities.uid')
            ->with(['identitys:id,uid,account_number,provider,user_type,status,linked_at,created_at,updated_at'])
            ->where('user_identities.uid', $uid)
            ->where('provider', $provider)
            ->first()?->toArray();
        if ($data) {
            $meta_json = json_decode($data['meta_json'] ?? '', true);
            unset($data['meta_json'], $data['provider'], $data['id']);
            if (!empty($meta_json)) {
                $data['last_name'] = $meta_json['last_name'] ?? $data['last_name'];
                $data['first_name'] = $meta_json['first_name'] ?? $data['first_name'];
                $data['picture'] = $meta_json['picture'] ?? $data['picture'];
            }
            $data['extend_json'] = json_decode($data['extend_json'] ?? '', true);
        }
        return $data;
    }
    public function updateEmail($uid, $account_number, $sms_code)
    {
        try {
            if (empty($sms_code)) {
                tryFun('sms_verify_err', sms_verify_err);
            }
            $is = feature('sms.register.verifyCode', $account_number, $sms_code);
            if (!$is) {
                tryFun('sms_verify_err', sms_verify_err);
            }
            $is  =  $this->userIdentitie->where('uid', '<>', $uid)->where('provider', email_str)->where('account_number', $account_number)->exists();
            if ($is)  tryFun('email_exists', email_exists);
            return $this->userIdentitie->where('uid', $uid)->where('provider', email_str)->update(['account_number' => $account_number, 'verified_at' => dayDateTime()]);
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), 'updateEmail');
            return 0;
        }
    }
    public function getUserInfoToken($user_info, $ip)
    {
        $info = [
            'id' => $user_info['uid'],
            'code' => $user_info['code'],
            'last_name' => $user_info['last_name'],
            'first_name' => $user_info['first_name'],
            'picture' => $user_info['picture'],
        ];
        $info['token'] = $this->jwtService->generateToken(
            ['id' => $info['id'], 'user_type' => $user_info['user_type'], 'provider' => $user_info['provider'], 'code' => $info['code']],
            $ip
        );
        return $info;
    }
    public function getUserIdToken($account_number, $provider, $ip)
    {
        $info =  $this->getUserInfo($account_number, $provider);
        if (empty($info)) tryFun('user_info_not', info_err);
        return  $this->getUserInfoToken($info, $ip);
    }
    public function create($userInfo)
    {
        if (isset($userInfo['tables'])) {
            $is =  $this->user->where('tables', $userInfo['tables'])->exists();
            if ($is) {
                return 0;
            }
        }
        $code =  $this->user->where('id', ">", 0)->orderBy('code', 'desc')->value('code') ?? 1000;
        $userInfo['code'] = (int)$code + 1;
        return $this->user->insertGetId(filterFields($userInfo, $this->user));
    }
    public function identitiesCreate($data)
    {
        return $this->userIdentitie->insertGetId(filterFields($data, $this->userIdentitie));
    }
    public function identitiesUpdate($account_number, $provider, $data)
    {
        return $this->userIdentitie->where('account_number', $account_number)->where('provider', $provider)->update(filterFields($data, $this->userIdentitie));
    }
    public function credentialCreate($data)
    {
        return $this->userCredential->insertGetId(filterFields($data, $this->userCredential));
    }
    public function getSecretHash($uid, $plain): bool
    {
        $identity_id =  $this->userIdentitie->where('uid', $uid)->pluck('id')->toArray();
        $hash = $this->userCredential->whereIn('identity_id', $identity_id)->pluck('secret_hash')->toArray();
        $is = false;
        foreach ($hash as $val) {
            if (empty($val)) continue;
            $is =  verifyPassword($plain,  $val);
            if ($is) {
                $is = true;
                break;
            }
        }
        return $is;
    }
    public function  show($id)
    {
        $data =  $this->user
            ->select(['users.*'])
            ->with(['userIdentitie:id,uid,account_number,user_type,status,verified_at,linked_at'])->where('users.id', $id)->first()?->toArray();
        $data['api_info'] =  feature('clients.show', $id);
        return $data;
    }
    //管理员创建用户
    public function createBasic($info)
    {
        $extend_json = $info['extend_json'] ?? [];
        $info['extend_json'] = json_encode($extend_json, JSON_UNESCAPED_UNICODE);
        Db::beginTransaction();
        $uid = $this->create($info);
        if($uid<=0){
            Db::rollBack();
            tryFun('user_exists', info_err);
        }
        //创建钱包
        feature('user.wallet.create', ['uid' => $uid]);
        Db::commit();
        return ['uid' => $uid];
    }
    public function updateBasic($id, $info)
    {
        $extend_json = $info['extend_json'] ?? [];
        $info['extend_json'] = json_encode($extend_json, JSON_UNESCAPED_UNICODE);
        $this->user->where('id', $id)->update(filterFields($info, $this->user));
        return ['uid' => $id];
    }
    public function createAccount($info)
    {

        Db::beginTransaction();
        $is_user =   $this->userIdentitie->where('account_number', $info['account_number'])->where('provider',  $info['provider'])->exists();
        if (!$is_user) {
            $identity_id = $this->identitiesCreate(['uid' => $info['uid'], 'status' => $info['status'], 'provider' => $info['provider'], 'account_number' => $info['account_number'], 'verified_at' => dayDateTime()]);
            $this->credentialCreate(['identity_id' => $identity_id, 'secret_hash' => getPasswordHash($info['password'] ?? '888888')]);
            Db::commit();
        } else {
            Db::rollBack();
            tryFun('account_number_exists', info_err);
        }
    }
    public function updateAccount($id, $info)
    {
        Db::beginTransaction();
        $is_user =   $this->userIdentitie->where('account_number', $info['account_number'])->where('provider',  $info['provider'])->where('uid', '<>', $info['uid'])->exists();
        if (!$is_user) {
            $this->identitiesUpdate($info['account_number'], $info['provider'], $info);
            if (!empty($info['password'])) {
                $is =  $this->userCredential->where('identity_id', $id)->update(['secret_hash' => getPasswordHash($info['password'])]);
                if (!$is) {
                    $this->userCredential->insert(['identity_id' => $id, 'secret_hash' => getPasswordHash($info['password'])]);
                }
            }
            Db::commit();
        } else {
            Db::rollBack();
            tryFun('account_number_exists', info_err);
        }
    }
}
