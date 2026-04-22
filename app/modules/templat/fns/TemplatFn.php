<?php

namespace app\modules\templat\fns;

use app\modules\templat\model\Templat;

class  TemplatFn
{
    protected $model;
    public function __construct(Templat $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model->where('uid', $info['jwtUserId'])
            ->when(isset($info['status']) && $info['status'] > -1, fn($query) => $query->where('status', $info['status']))
            ->when(isset($info['type']) && $info['type'] > -1, fn($query) => $query->where('type', $info['type']))
            ->when(!empty($info['seek']), function ($query) use ($info) {
                return $query->where(function ($query) use ($info) {
                    return $query->where('name', 'like', "%{$info['seek']}%")
                        ->orWhere('title', 'like', "%{$info['seek']}%");
                });
            });
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('id', 'desc')
                ->orderBy('sort', 'asc')
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
        if ($is) tryFun('tag_name_exists', info_err);
        $info['uid'] = $info['jwtUserId'];
        return $this->model->insertGetId(filterFields($info, $this->model));
    }
    public function update($id, $info)
    {
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('name', $info['name'])->exists();
        if ($is) tryFun('tag_name_exists', info_err);
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
    public function getNameIndex($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model
            ->select(['id', 'name', 'title', 'type', 'content', 'use_total'])
            ->where('uid', $info['jwtUserId'])
            ->when(isset($info['status']) && $info['status'] > -1, fn($query) => $query->where('status', $info['status']))
            ->when(isset($info['type']) && $info['type'] > -1, fn($query) => $query->where('type', $info['type']))
            ->when(!empty($info['seek']), function ($query) use ($info) {
                return $query->where(function ($query) use ($info) {
                    return $query->where('name', 'like', "%{$info['seek']}%")
                        ->orWhere('title', 'like', "%{$info['seek']}%");
                });
            });
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('updated_at', 'desc')
                ->orderBy('sort', 'asc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
}
