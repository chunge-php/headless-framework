<?php

namespace app\modules\user\addressbook\fns;

use app\modules\user\addressbook\model\AddressBook;

class  AddressbookFn
{
    protected $model;
    public function __construct(AddressBook $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {

        $where = $this->model
            ->where('uid', $info['jwtUserId'])
            ->when(!empty($info['tags_id']), function ($query) use ($info) {
                return $query->whereIn('id', function ($query) use ($info) {
                    return $query->from('tag_books')->select('target_id')->whereIn('tags_id', explode(',', $info['tags_id']))->where('target_type', addressbook_str)->pluck('target_id')->toArray();
                });
            })
            ->when(!empty($info['phone']), function ($query) use ($info) {
                if (!is_array($info['phone'])) {
                    $info['phone'] = explode(',', $info['phone']);
                }
                return $query->whereIn('phone', $info['phone']);
            })
            ->when(!empty($info['seek']), function ($query) use ($info) {
                return $query->where(function ($query) use ($info) {
                    return $query->where('last_name', 'like', "%{$info['seek']}%")
                        ->orWhere('first_name', 'like', "%{$info['seek']}%")
                        ->orWhere('phone', 'like', "%{$info['seek']}%")
                        ->orWhere('email', 'like', "%{$info['seek']}%");
                });
            });
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            $ids = array_map(fn($r) => (int)$r['id'], $list);
            try {
                $tagMap = feature('tag.mapByTargets', $info['jwtUserId'],   $info['target_type'] ?? addressbook_str, $ids) ?? [];
                foreach ($list as &$r) {
                    $r['tags'] = $tagMap[$r['id']] ?? [];
                }
            } catch (\Exception $e) {
            }

            return ['total' => $total, 'list' => $list];
        }
    }
    public function create($info)
    {
        if (!empty($info['phone'])) {
            $info['phone']   = phone_to_digits($info['phone'] ?? '');
            $is  = $this->model->where('uid', $info['jwtUserId'])->where('phone', $info['phone'])->exists();
            if ($is) tryFun('phone_exists', info_err);
        }
        if (!empty($info['email'])) {
            $info['email'] =   str_replace(' ', '', $info['email']);
            $is  = $this->model->where('uid', $info['jwtUserId'])->where('email', $info['email'])->exists();
            if ($is) tryFun('email_exists', info_err);
        }
        $info['birthday'] = toYmdOrEmpty($info['birthday'] ?? '');
        $info['uid'] = $info['jwtUserId'];
        $id = $this->model->insertGetId(filterFields($info, $this->model));
        try {
            feature('tag.createTagBook', $info['jwtUserId'], $info['target_type'] ?? addressbook_str, $id, $info['tags_id']);
        } catch (\Exception $e) {
        }

        return $id;
    }
    public function update($id, $info)
    {
        if (!empty($info['phone'])) {
            $info['phone']   = phone_to_digits($info['phone'] ?? '');
            $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('phone', $info['phone'])->exists();
            if ($is) tryFun('phone_exists', info_err);
        }
        if (!empty($info['email'])) {
            $info['email'] =   str_replace(' ', '', $info['email']);
            $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('email', $info['email'])->exists();
            if ($is) tryFun('email_exists', info_err);
        }
        $info['birthday'] = toYmdOrEmpty($info['birthday'] ?? '');
        $is = $this->model->where('id', $id)->update(filterFields($info, $this->model));
        if ($is) {
            try {
                feature('tag.createTagBook', $info['jwtUserId'],  $info['target_type'] ?? addressbook_str, $id, $info['tags_id']);
            } catch (\Exception $e) {
            }
        }
        return $is;
    }
    public function show($info)
    {
        $info =  $this->model->where('id', $info['id'])->where('uid', $info['jwtUserId'])->first()?->toArray();
        try {
            $tagMap = feature('tag.mapByTargets', $info['uid'],   addressbook_str, [$info['id']]) ?? [];
            $info['tags'] = $tagMap[$info['id']] ?? [];
        } catch (\Exception $e) {
        }
        return $info;
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        try {
            feature('tag.deleteTagBook',  addressbook_str, $info['id']);
        } catch (\Exception $e) {
        }
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['jwtUserId'])->delete();
    }

    public function tagMerge($info)
    {
        try {
            return feature('tag.tagMerge', $info['jwtUserId'],  addressbook_str, $info['target_id'], $info['tags_id'], $info['merge_tags_id']);
        } catch (\Exception $e) {
            tryFun('tag_merge_error', info_err);
        }
    }
    public function getDateUser($uid, $dateArr)
    {
        foreach ($dateArr as &$r) {
            $r = substr($r, 5);
        }
        return $this->model->select(['id', 'last_name', 'first_name', 'phone', 'email', 'birthday'])->where('uid', $uid)->whereIn('month_day', $dateArr)->get()->toArray();
    }
}
