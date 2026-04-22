<?php

namespace app\modules\sms\body\model;


use app\model\BaseModel;
class LesseeLog extends BaseModel
{
  //第三方租户信息
  //begin_fillable

  protected $fillable = ['name', 'code', 'sms_batch_lots_id'];//end_fillable
}
