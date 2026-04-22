<?php

namespace app\modules\user\wallet\model;


use app\model\BaseModel;
class BillSeq extends BaseModel
{
  //账单编号生成记录
  //begin_fillable

  protected $fillable = ['biz', 'ymd', 'val', 'updated_at'];//end_fillable
}
