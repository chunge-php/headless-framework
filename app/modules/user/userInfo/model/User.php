<?php

namespace app\modules\user\userInfo\model;


use app\model\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends BaseModel
{
  use SoftDeletes;

  //用户基础表
  //begin_fillable
  protected $fillable = [
        'code',
        'last_name',
        'first_name',
        'phone',
        'picture',
        'state',
        'updated_at',
        'extend_json',
        'tables'
    ];
  //end_fillable

  public function userIdentitie()
  {
    return $this->hasMany(UserIdentitie::class, 'uid', 'id')->whereIn('user_type', [user_str,dev_str]);
  }
}
