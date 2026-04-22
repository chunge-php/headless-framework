<?php

namespace app\modules\sms\body\model;


use app\model\BaseModel;
class SmsBill extends BaseModel
{
  //短信月统计
  //begin_fillable

  protected $fillable = [
        'invoice_number',
        'years',
        'months',
        'total_price',
        'sms_price',
        'mms_price',
        'email_price',
        'total_sms',
        'total_mms',
        'total_email',
        'uid',
        'updated_at'
    ];//end_fillable
}
