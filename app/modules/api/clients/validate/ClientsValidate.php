<?php

namespace app\modules\api\clients\validate;

use think\Validate;

class ClientsValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'name'    => 'require',
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'name.require'    => 'name_not_input',
    ];
    // 场景验证
    public $scene = [
        'POST_sendSms' => [''],
    ];
}


