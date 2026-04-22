<?php

namespace app\modules\user\userdata\validate;

use think\Validate;

class UserdataValidate extends Validate
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
    ];
}


