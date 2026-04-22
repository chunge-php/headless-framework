<?php

namespace app\modules\groupUser\model;


use app\model\BaseModel;
class GroupUser extends BaseModel
{
  //分组用户管理
  //begin_fillable

  protected $fillable = ['uid', 'name', 'total', 'file_url', 'description', 'updated_at', 'channel_type'];
  
  //end_fillable

  
}
