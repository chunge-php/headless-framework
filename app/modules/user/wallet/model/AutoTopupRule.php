<?php

namespace app\modules\user\wallet\model;


use app\model\BaseModel;
class AutoTopupRule extends BaseModel
{
  //自动充值规则
  //begin_fillable

  protected $fillable = [
        'uid',
        'wallets_id',
        'enabled',
        'threshold_cents',
        'topup_amount_cents',
        'reminder_price',
        'reminder_status',
        'cooldown_sec',
        'today_count',
        'last_run_at',
        'updated_at'
    ];//end_fillable
}
