<?php

namespace app\modules\game\model;


use app\model\BaseModel;

class GameLog extends BaseModel
{

  //中奖记录
  //begin_fillable

  protected $fillable = ['uid', 'to', 'type', 'body', 'result', 'updated_at'];//end_fillable
}
