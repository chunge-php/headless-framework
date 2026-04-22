<?php

namespace app\modules\user\addressbook\model;


use app\model\BaseModel;
class AddressBook extends BaseModel
{
  //通讯录
  //begin_fillable

  protected $fillable = ['uid', 'last_name', 'first_name', 'phone', 'email', 'birthday', 'month_day', 'updated_at'];//end_fillable
}
