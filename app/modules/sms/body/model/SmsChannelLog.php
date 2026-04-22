<?php

namespace app\modules\sms\body\model;


use app\model\BaseModel;
class SmsChannelLog extends BaseModel
{
  //渠道来源发送记录
  //begin_fillable

  protected $fillable = ['to', 'channel_type', 'uid'];//end_fillable
}
