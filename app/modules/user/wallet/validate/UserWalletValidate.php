<?php

namespace app\modules\user\wallet\validate;

use think\Validate;

class UserWalletValidate extends Validate
{
    protected $rule = [
        'name'    => 'require',
        'amount'=>'require',
    ];
    protected $message = [
        'name.require'    => 'name_not_input',
        'amount.require'    => 'amount_not_input',
    ];
    // 场景验证
    public $scene = [
        'POST_create' => ['name'],
        'POST_recharge'=>['amount']
    ];
}


