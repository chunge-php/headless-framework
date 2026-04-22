<?php

namespace app\modules\batchLog\model;


use app\model\BaseModel;
class BatchLog extends BaseModel
{
  //批次任务记录
  //begin_fillable

  protected $fillable = [
        'name',
        'uid',
        'scope_id',
        'scope',
        'status',
        'total',
        'success',
        'fail',
        'describe',
        'updated_at',
        'start_time',
        'end_time',
        'duration',
        'exists',
        'invalid_phones'
    ];//end_fillable
}
