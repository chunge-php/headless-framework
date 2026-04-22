<?php

namespace app\modules\jobTask\model;


use app\model\BaseModel;
class JobDefinition extends BaseModel
{
  //任务定义
  //begin_fillable

  protected $fillable = [
        'name',
        'kind',
        'price_id',
        'status',
        'run_status',
        'run_total',
        'meta_json',
        'next_days',
        'next_time',
        'content',
        'uid',
        'updated_at'
    ];//end_fillable

    public function getMetaJsonAttribute($value)
    {
        return !empty($value) ? json_decode($value, true) : null;
    }
}
