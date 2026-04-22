<?php

namespace app\modules\game\model;


use app\model\BaseModel;

class GameUser extends BaseModel
{
  const CREATED_AT = null;
  const UPDATED_AT = null;
  //用户选择的游戏
  //begin_fillable

  protected $fillable = ['uid', 'code', 'type', 'body', 'options', 'pre', 'switch']; //end_fillable
  public function getOptionsAttribute($value)
  {
    if (empty($value)) {
      return [];
    }
    return json_decode($value, true);
  }
  public function getPreAttribute($value)
  {
    if (empty($value)) {
      return [];
    }
    return json_decode($value, true);
  }
  public function getSwitchAttribute($value)
  {
    if (empty($value)) {
      return [];
    }
    return json_decode($value, true);
  }
}
