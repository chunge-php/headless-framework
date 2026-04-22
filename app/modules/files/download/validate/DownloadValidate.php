<?php

namespace app\modules\files\download\validate;

use think\Validate;

class DownloadValidate extends Validate
{
    protected $rule = [
        'id'    => 'require',
        'name'    => 'require',
        'preset'    => 'require',
    ];
    protected $message = [
        'id.require'    => 'id_not_select',
        'preset.require'    => 'preset_input',
    ];
    // 场景验证
    public $scene = [
        'GET_templatDownload' => ['preset'],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
    ];
}


