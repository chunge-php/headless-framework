<?php

namespace app\modules\clutter\legal\model;


use app\model\BaseModel;

class Legal extends BaseModel
{
  //用户隐私协议或其大文本存储表
  //begin_fillable

  protected $fillable = ['name', 'title', 'slug', 'locale', 'content', 'updated_at']; //end_fillable
}
