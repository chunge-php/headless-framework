<?php

namespace app\model;

use support\Model;
use Illuminate\Support\Arr;

class BaseModel extends Model
{

    public const  state_zero = 0;
    public const state_one = 1;
    public const state_two = 2;
    public const state_three = 3;
    protected function serializeDate($date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    /**
     * 过滤数据库不存在的字段
     * @param array $data
     * @return array
     */
    public function filterFields(array $data): array
    {
        $fillable = $this->getFillable();
        return Arr::only($data, $fillable);
    }

    /**
     * 插入数据时自动过滤字段
     * @param array $data
     * @return int
     */
    public function safeInsert(array $data): int
    {
        $filteredData = $this->filterFields($data);
        return $this->insert($filteredData);
    }
    /**
     * 更新数据时自动过滤字段
     * @param array $data
     * @param array $conditions
     * @return int
     */
    public function safeUpdate(array $data, array $conditions): int
    {
        $filteredData = $this->filterFields($data);
        return $this->where($conditions)->update($filteredData);
    }
}
