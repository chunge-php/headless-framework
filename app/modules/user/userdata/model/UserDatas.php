<?php

namespace app\modules\user\userdata\model;


use app\model\BaseModel;
class UserDatas extends BaseModel
{
  //用户资料
  //begin_fillable

  protected $fillable = [
        'company_name',
        'last_name',
        'first_name',
        'country',
        'address1',
        'address2',
        'city',
        'state',
        'zip_code',
        'uid',
        'updated_at'
    ];//end_fillable
}
