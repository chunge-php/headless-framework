<?php

namespace app\modules\sms\body\model;


use app\model\BaseModel;
class SmsBatchLot extends BaseModel
{
  //短信发送记录批次
  //begin_fillable

  protected $fillable = [
        'code',
        'uid',
        'scope_id',
        'price',
        'price_id',
        'total_money',
        'send_type',
        'subscribe_type',
        'status',
        'mms_url',
        'years',
        'months',
        'days',
        'consume_number',
        'executed_count',
        'success_total',
        'error_total',
        'subject',
        'content',
        'unusual_msg',
        'updated_at'
    ];//end_fillable

    protected $appends = ['send_type_name', 'status_name','subscribe_type_name'];

    public function getSendTypeNameAttribute()
    {
      
      return getTemplatTypeName($this->send_type);
    }
    public function getStatusNameAttribute()
    {
      return getStatusNameAttribute($this->status);
    }
    public function getSubscribeTypeNameAttribute()
    {
      return getSubscribeTypeName($this->subscribe_type);
    }
}
