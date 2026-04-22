<?php

namespace app\modules\user\card\fns;

use app\modules\user\card\model\Card;

class  CardFn
{
    protected $model;
    public function __construct(Card $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model->select([
            'id',
            'name',
            'accttype',
            'expiry',
            'address',
            'line2',
            'city',
            'region',
            'country',
            'postal',
            'company',
            'later_number',
            'status',
            'default_state',
            'created_at',
            'updated_at'
        ])->where('uid', $info['jwtUserId']);
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('default_state', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }

    public function show($info)
    {
        return $this->model->select([
            'id',
            'name',
            'accttype',
            'expiry',
            'address',
            'line2',
            'city',
            'region',
            'country',
            'postal',
            'company',
            'later_number',
            'status',
            'default_state',
            'created_at',
            'updated_at'
        ])->where('id', $info['id'])->where('uid', $info['jwtUserId'])->first()?->toArray();
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['jwtUserId'])->delete();
    }
    public function defaultState($info)
    {
        if ($info['state'] == state_one) {
            try {
                feature('user.wallet.autoTopupStatus', $info['uid'], state_one);
            } catch (\Exception $e) {
            }
        }
        if ($info['state'] == state_one) {
            $this->model->where('id', '<>', $info['id'])->where('uid', $info['jwtUserId'])->update(['default_state' => state_zero]);
        }
        return $this->model->where('id', $info['id'])->where('uid', $info['jwtUserId'])->update(['default_state' => $info['state']]);
    }
    public function setState($info)
    {
        if ($info['state'] == state_one) {
            try {
                feature('user.wallet.autoTopupStatus', $info['uid'], state_one);
            } catch (\Exception $e) {
            }
        }
        return $this->model->where('id', $info['id'])->where('uid', $info['jwtUserId'])->update(['status' => $info['state']]);
    }
}
