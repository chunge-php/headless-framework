<?php

namespace app\modules\user\wallet\model;


use app\model\BaseModel;

class Wallet extends BaseModel
{
  //钱包管理
  //begin_fillable
  protected $fillable = ['uid', 'balance', 'account_sid', 'updated_at'];
  //end_fillable
}
