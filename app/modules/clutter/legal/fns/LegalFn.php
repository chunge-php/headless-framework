<?php

namespace app\modules\clutter\legal\fns;

use app\modules\clutter\legal\model\Legal;

class  LegalFn
{
    protected $model;
    public function __construct(Legal $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1):array
    {
        $where = $this->model;
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
        $is  = $this->model->where('name', $info['name'])->exists();
        if ($is) tryFun('tag_name_exists', info_err);
        return $this->model->insertGetId(filterFields($info, $this->model));
    }
    public function update($id,$info)
    {
        $is  = $this->model->where('id', '<>', $id)->where('name', $info['name'])->exists();
        if ($is) tryFun('tag_name_exists', info_err);
        return $this->model->where('id', $id)->update(filterFields($info, $this->model));
    }
    public function show($info)
    {
        return $this->model->where('id', $info['id'])->first()?->toArray();
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->model->whereIn('id', $info['id'])->delete();
    }
    public function slugName($slug)
    {
        return $this->model->where('slug', $slug)->first()?->toArray();
    }
}

