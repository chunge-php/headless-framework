<?php

namespace app\modules\sms\body\model;


use app\model\BaseModel;
class SmsBatchSourc extends BaseModel
{
  //联系人来源
  //begin_fillable

  protected $fillable = ['account_number', 'source_id', 'source_type', 'sms_batch_lots_id', 'updated_at'];//end_fillable
}
