<?php

namespace app\modules\user\card\model;


use app\model\BaseModel;
class Card extends BaseModel
{
  //信用卡管理
  //begin_fillable

  protected $fillable = [
        'name',
        'uid',
        'acctid',
        'profileid',
        'accttype',
        'expiry',
        'token',
        'address',
        'line2',
        'city',
        'region',
        'country',
        'postal',
        'company',
        'later_number',
        'cvv',
        'status',
        'default_state',
        'response_data',
        'updated_at'
    ];//end_fillable

    protected $appends=['status_name','default_state_name'];

    public function getStatusNameAttribute()
    {
        return getBaseStatusName($this->status);
    }
    public function getDefaultStateNameAttribute()
    {
        return getBaseState($this->default_state);
    }
}
