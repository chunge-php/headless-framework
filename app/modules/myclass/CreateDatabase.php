<?php

namespace app\modules\myclass;

use support\Db;

final  class CreateDatabase
{

    protected $uid;
    protected $identity_id;

    public function createInitServe()
    {

        $this->createUser();
        $this->createIdentitie('username', 'admin', admin_str);
        $this->createCredential();
        $this->createWallet();
        $this->createSmsPrice();
        $this->createLegal();

        $this->createUser(1001, 'eatery');
        $this->createIdentitie('username', 'eatery@qq.com', dev_str);
        $this->createCredential();
        $this->createWallet();
        $this->createSmsPrice();
    }
    public function createLegal()
    {
        $user_agreement = file_get_contents(base_path('resource/stubs/terms-of-service.stub'));
        $privacy_policy = file_get_contents(base_path('resource/stubs/privacy-policy.stub'));
        Db::table('legals')->insert([
            'id' => 1,
            'name' => 'Terms of Service',
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'locale' => 'en-US',
            'content' => (string)$user_agreement,
        ]);
        Db::table('legals')->insert([
            'id' => 2,
            'name' => 'Privacy Policy',
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'locale' => 'en-US',
            'content' => (string)$privacy_policy,
        ]);
    }
    public function createSmsPrice()
    {
        Db::table('sms_prices')->insert([
            [
                'name' => 'SMS',
                'price' => 0.05,
                'type' => state_zero,
                'remark' => 'SMS 6/0.05 USD',
            ],
            [
                'name' => 'MMS',
                'price' => 0.1,
                'type' => state_one,
                'remark' => 'MMS 6/0.1 USD',
            ],
            [
                'name' => 'Email',
                'price' => 0.05,
                'type' => state_two,
                'remark' => 'Email 0.05 USD',
            ]
        ]);
    }
    public function createWallet()
    {
        Db::table('wallets')->insert([
            'uid' => $this->uid,
            'account_sid' => md5($this->uid),
            'balance' => 100,
        ]);
    }
    private function createUser($code = 1000, $first_name = 'Administrator')
    {
        $this->uid = Db::table('users')->insertGetId([
            'code' => $code,
            'last_name' => '',
            'first_name' => $first_name,
            'state' => state_one,
        ]);
        return $this->uid;
    }
    private function createIdentitie($provider, $account_number, $user_type)
    {
        $this->identity_id = Db::table('user_identities')->insertGetId([
            'uid' => $this->uid,
            'provider' => $provider,
            'account_number' => $account_number,
            'user_type' => $user_type,
            'status' => state_one,
            'verified_at' => dayDateTime(),
        ]);
        return $this->identity_id;
    }
    private function createCredential()
    {
        Db::table('user_credentials')->insert([
            'identity_id' => $this->identity_id,   // 确保这是真实存在的 user_identities.id
            'secret_hash' => getPasswordHash(),                // VARCHAR(255) 建议
        ]);
    }
}
