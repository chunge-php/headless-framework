<?php

namespace app\modules\user\addressbook\validate;

use think\Validate;

class AddressbookValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'name'    => 'require',
        'target_id'    => 'require',
        'tags_id'    => 'require',
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'name.require'    => 'name_not_input',
        'target_id.require'    => 'target_id_select',
        'tags_id.require'    => 'tags_id_select',
    ];
    // 场景验证
    public $scene = [
        'POST_create' => [''],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
        'POST_tagMerge'=>['target_id','tags_id']
    ];
}


