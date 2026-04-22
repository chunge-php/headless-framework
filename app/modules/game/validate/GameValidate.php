<?php

namespace app\modules\game\validate;

use think\Validate;

class GameValidate extends Validate
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
        'POST_create' => [''],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
    ];
}


