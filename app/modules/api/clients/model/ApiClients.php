<?php

namespace app\modules\api\clients\model;


use app\model\BaseModel;
class ApiClients extends BaseModel
{
  //客户端key值管理
  //begin_fillable

  protected $fillable = ['uid', 'access_key', 'secret_key', 'status', 'updated_at'];//end_fillable
}
