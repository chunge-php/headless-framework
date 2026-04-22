<?php

namespace app\modules\auth\google\validate;

use think\Validate;

class GoogleValidate extends Validate
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


