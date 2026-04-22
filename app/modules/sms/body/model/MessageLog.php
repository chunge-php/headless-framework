<?php

namespace app\modules\sms\body\model;


use app\model\BaseModel;

class MessageLog extends BaseModel
{
  //MessageLog model & migration
  //begin_fillable

  protected $fillable = [
        'uid',
        'sms_batch_lots_id',
        'account_number',
        'type',
        'status',
        'total_money',
        'consume_number',
        'unusual_msg',
        'updated_at'
    ]; //end_fillable

  protected $appends = ['type_name', 'status_name'];

  public function getTypeNameAttribute()
  {
    
    return getTemplatTypeName($this->type);
  }
  public function getStatusNameAttribute()
  {
    return getStatusNameAttribute($this->status);
  }
}
