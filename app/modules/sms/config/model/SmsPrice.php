<?php

namespace app\modules\sms\config\model;


use app\model\BaseModel;
class SmsPrice extends BaseModel
{
  //短信价格配置
  //begin_fillable

  protected $fillable = ['name', 'price', 'type', 'remark', 'updated_at'];
  
  //end_fillable

  protected $appends = ['type_name'];

  public function getTypeNameAttribute()
  {
    return getTemplatTypeName($this->type);
  }
}
