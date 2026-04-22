<?php

namespace app\modules\sms\register\model;


use app\model\BaseModel;

class CodeLog extends BaseModel
{
  //短信验证码发送记录
  //begin_fillable
  protected $fillable = ['account_number', 'code', 'auto_type', 'msg', 'updated_at'];
  //end_fillable
}
