<?php

namespace app\modules\user\userInfo\validate;

use think\Validate;

class UserUserinfoValidate extends Validate
{
    protected $rule = [
        'name'    => 'require',
    ];
    protected $message = [
        'name.require'    => 'name_not_input',
    ];
    // 场景验证
    public $scene = [
        'POST_create' => ['name']
    ];
}


