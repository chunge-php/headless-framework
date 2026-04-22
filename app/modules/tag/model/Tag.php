<?php

namespace app\modules\tag\model;


use app\model\BaseModel;

class Tag extends BaseModel
{
  //标签管理
  //begin_fillable

  protected $fillable = ['name', 'description', 'colour', 'target_type', 'uid', 'updated_at'];

  //end_fillable
  public function tagBook()
  {
    return $this->hasMany(TagBook::class, 'tags_id', 'id')->select(['id', 'tags_id', 'target_id', 'target_type', 'uid']);
  }
}
