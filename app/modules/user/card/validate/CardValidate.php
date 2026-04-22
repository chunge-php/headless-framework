<?php

namespace app\modules\user\card\validate;

use think\Validate;

class CardValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'name'    => 'require',
        'state'=>'require',
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'name.require'    => 'name_not_input',
        'state.require'=>'state_not_select',
    ];
    // 场景验证
    public $scene = [
        'POST_defaultState' => ['id','state'],
        'POST_setState' => ['id','state'],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
    ];
}


