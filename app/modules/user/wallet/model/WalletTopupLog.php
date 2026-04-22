<?php

namespace app\modules\user\wallet\model;


use app\model\BaseModel;
class WalletTopupLog extends BaseModel
{
  //充值流水
  //begin_fillable

  protected $fillable = [
        'uid',
        'code',
        'years',
        'months',
        'days',
        'cards_id',
        'amount_cents',
        'before_balance_cents',
        'after_balance_cents',
        'status',
        'response_data',
        'updated_at'
    ];//end_fillable
}
