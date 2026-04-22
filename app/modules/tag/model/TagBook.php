<?php

namespace app\modules\tag\model;


use app\model\BaseModel;
class TagBook extends BaseModel
{
  //绑定标签
  //begin_fillable

  protected $fillable = ['tags_id', 'target_type', 'target_id', 'uid', 'updated_at'];//end_fillable
}
