<?php

namespace app\modules\user\userdata\fns;

use app\modules\user\userdata\model\UserDatas;

class  UserdataFn
{
    protected $model;
    public function __construct(UserDatas $model)
    {
        $this->model = $model;
    }
    public function create($info)
    {
        $id  = $this->model->where('uid', $info['jwtUserId'])->value('id');
        if ($id) {
            $is =  $this->model->where('id', $id)->update(filterFields($info, $this->model));
            return $id;
        } else {
            $info['uid'] = $info['jwtUserId'];
            return $this->model->insertGetId(filterFields($info, $this->model));
        }
    }

    public function show($info)
    {
        return $this->model->where('uid', $info['jwtUserId'])->first()?->toArray();
    }
  
}
