<?php

namespace app\modules\test\fns;

use app\modules\test\model\Test;

class  TestFn
{
    protected $model;
    public function __construct(Test $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1):array
    {
        $where = $this->model->where('uid', $info['jwtUserId']);
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
    public function create($info)
    {
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('name', $info['name'])->exists();
        if ($is) return error('tag_name_exists', info_err);
        return $this->model->insertGetId(filterFields($info, $this->model));
    }
    public function update($id,$info)
    {
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('name', $info['name'])->exists();
        if ($is) return error('tag_name_exists', info_err);
        return $this->model->where('id', $id)->update(filterFields($info, $this->model));
    }
    public function show($info)
    {
        return $this->model->where('id', $info['id'])->where('uid', $info['jwtUserId'])->first()?->toArray();
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['jwtUserId'])->delete();
    }
}

