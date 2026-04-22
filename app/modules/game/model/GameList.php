<?php

namespace app\modules\game\model;


use app\model\BaseModel;

class GameList extends BaseModel
{
  const CREATED_AT = null;
  const UPDATED_AT = null;

  //游戏配置
  //begin_fillable

  protected $fillable = ['options', 'type']; //end_fillable

  public function getOptionsAttribute($value)
  {
    if (empty($value)) {
      return [];
    }
    return json_decode($value, true);
  }
}
