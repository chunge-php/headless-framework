<?php

namespace app\modules\configItem\model;


use app\model\BaseModel;

class ConfigItem extends BaseModel
{
  //单一配置
  //begin_fillable

  protected $fillable = ['name', 'signs', 'values', 'updated_at'];
  //end_fillable
}
