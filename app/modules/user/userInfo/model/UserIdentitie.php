<?php

namespace app\modules\user\userInfo\model;



use app\model\BaseModel;

class UserIdentitie extends BaseModel
{
  //用户身份映射
  //begin_fillable
  protected $fillable = [
        'uid',
        'provider',
        'account_number',
        'user_type',
        'status',
        'meta_json',
        'verified_at',
        'linked_at',
        'updated_at'
    ];

  //end_fillable

  public function getInfo($provider, $account_number)
  {
    return $this->where('provider', $provider)->where('account_number', $account_number)->first()?->toArray();
  }
  public function identitys()
  {
    return $this->hasMany(UserIdentitie::class, 'uid', 'uid');
  }
}
