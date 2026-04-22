<?php

namespace app\modules\templat\model;


use app\model\BaseModel;
class Templat extends BaseModel
{
  //模板管理
  //begin_fillable

  protected $fillable = ['name', 'title', 'type', 'content', 'status', 'sort', 'use_total', 'uid', 'updated_at'];
  
  //end_fillable

  protected $appends = ['status_name','type_name'];
  public function getStatusNameAttribute()
  {
    return getBaseStatusName($this->status);
  }
  public function getTypeNameAttribute()
  {
   return getTemplatTypeName($this->type);
  }
}
