<?php

namespace app\modules\batchLog\fns;

use app\modules\batchLog\model\BatchLog;

class  BatchlogFn
{
    protected $model;
    public function __construct(BatchLog $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model->where('uid', $info['jwtUserId'])
            ->when(!empty($info['scope']), fn($query) => $query->where('scope', $info['scope']))
            ->when(!empty($info['scope_id']), fn($query) => $query->where('scope_id', $info['scope_id']))
            ->when(isset($info['status']) && $info['status'] > -1, fn($query) => $query->where('status', $info['status']))
            ->when(!empty($info['seek']), fn($query) => $query->where('name', 'like', '%' . $info['seek'] . '%'));
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

    public function create($data)
    {
        return $this->model->insertGetId(filterFields($data, $this->model));
    }
    public function update($data)
    {
        return $this->model->where('id', $data['id'])->update($data);
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
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['uid'])->delete();
    }
}
