<?php

namespace app\modules\jobTask\validate;

use think\Validate;

class JobtaskValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'name'    => 'require',
        'kind'    => 'require',
        'price_id'    => 'require'
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'name.require'    => 'name_not_input',
        'kind.require'    => 'kind_not_input',
        'price_id.require'    => 'price_not_exists',
    ];
    // 场景验证
    public $scene = [
        'POST_create' => ['name','kind','price_id'],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
    ];
}


