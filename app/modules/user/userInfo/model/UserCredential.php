<?php

namespace app\modules\user\userInfo\model;



use app\model\BaseModel;

class UserCredential extends BaseModel
{
  //凭证：密码
  //begin_fillable
  protected $fillable = ['identity_id', 'secret_hash', 'updated_at'];
  //end_fillable

  public function getSecretHash($identity_id, $plain): bool
  {
    $hash =  $this->where('identity_id', $identity_id)->value('secret_hash');
    return verifyPassword($plain,  $hash);
  }
}
