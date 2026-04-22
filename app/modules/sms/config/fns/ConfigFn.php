<?php

namespace app\modules\sms\config\fns;

use app\modules\sms\config\model\SmsPrice;

class  ConfigFn
{
    protected $model;
    public function __construct(SmsPrice $model)
    {
        $this->model = $model;
    }
    public function priceName()
    {
        return $this->model->select(['id', 'name', 'type', 'price', 'remark'])->get()->toArray();
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model
            ->when(!empty($info['seek']), function ($query) use ($info) {
                $query->where('name', 'like', '%' . $info['seek'] . '%');
            });
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
        if ($is) tryFun('name_exists', info_err);
        return $this->model->insertGetId(filterFields($info, $this->model));
    }
    public function update($id, $info)
    {
        $is  = $this->model->where('id', '<>', $id)->where('name', $info['name'])->exists();
        if ($is) tryFun('name_exists', info_err);
        return $this->model->where('id', $id)->update(filterFields($info, $this->model));
    }

    public function show($info)
    {
        return $this->model->where('id', $info['id'])->first()?->toArray();
    }
    public function getPriceId($type)
    {
        return $this->model->where('type', $type)->value('id')??0;
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->model->whereIn('id', $info['id'])->delete();
    }
}
