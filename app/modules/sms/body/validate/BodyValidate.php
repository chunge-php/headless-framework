<?php

namespace app\modules\sms\body\validate;

use think\Validate;

class BodyValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'price_id'    => 'require',
        'batch_lot_id'    => 'require',
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'price_id.require'    => 'price_not_exists',
        'batch_lot_id.require'    => 'price_not_exists',
    ];
    // 场景验证
    public $scene = [
        'POST_create' => ['price_id'],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
        'POST_anewSend'=>['batch_lot_id']
    ];
}


