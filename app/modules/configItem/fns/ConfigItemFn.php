<?php

namespace app\modules\configItem\fns;

use app\modules\configItem\model\ConfigItem;

class  ConfigItemFn
{
    protected $model;
    public function __construct()
    {
        $this->model = new ConfigItem();
    }

    public function index($info, $limit = 20, $offset = 1): array
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
        $info['values'] =    json_encode($info['values'], JSON_UNESCAPED_UNICODE);
        $id  = $this->model->where('signs', $info['signs'])->value('id');
        if ($id) {
            $this->model->where('id', $id)->update(filterFields($info, $this->model));
            return $id;
        } else {
            return $this->model->insertGetId(filterFields($info, $this->model));
        }
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
    public function getName($signs)
    {
        return $this->model->select(['id', 'signs', 'name', 'values'])->where('signs', $signs)->first()?->toArray();
    }
}
