<?php

namespace app\modules\api\clients\validate;

use think\Validate;

class ApiClientsValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'body'    => 'require',
        'account_number'    => 'require',
        'send_type'    => 'require'
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'body.require'    => 'body_not',
        'account_number.require'    => 'account_number_not',
        'send_type.require'    => 'send_type_not',
    ];
    // 场景验证
    public $scene = [
        'POST_sendSms' => ['body','account_number','send_type'],
    ];
}


