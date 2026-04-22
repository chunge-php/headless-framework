<?php

namespace app\modules\user\wallet\model;


use app\model\BaseModel;

class Bill extends BaseModel
{
  //充值和消费账单记录
  //begin_fillable

  protected $fillable = [
        'uid',
        'code',
        'batch_id',
        'total_money',
        'balance',
        'send_type',
        'bill_type',
        'years',
        'months',
        'days',
        'updated_at'
    ]; //end_fillable

  protected $appends = ['send_type_name', 'bill_type_name'];

  public function getSendTypeNameAttribute()
  {
    return getTemplatTypeName($this->send_type);
  }
  public function getBillTypeNameAttribute()
  {
    return getBillTypeNameAttribute($this->bill_type);
  }
}
