<?php

namespace app\modules\groupUser\fns;

use app\modules\groupUser\model\GroupUser;

class  GroupUserFn
{
    protected $model;
    public function __construct(GroupUser $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model->where('uid', $info['jwtUserId'])
            ->when(!empty($info['tags_id']), function ($query) use ($info) {
                return $query->whereIn('id', function ($query) use ($info) {
                    return $query->from('tag_books')->select('target_id')->whereIn('tags_id', explode(',', $info['tags_id']))->where('target_type', addressbook_str)->pluck('target_id')->toArray();
                });
            })
            ->when(!empty($info['seek']), function ($query) use ($info) {
                return $query->where(function ($query) use ($info) {
                    return $query->where('name', 'like', "%{$info['seek']}%");
                });
            });;
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
                $tagMap = feature('tag.mapByTargets', $info['jwtUserId'],  $info['target_type'] ?? group_users_str, $ids) ?? [];
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
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('name', $info['name'])->exists();
        if ($is) tryFun('name_exists', info_err);
        $info['uid'] = $info['jwtUserId'];
        $id = $this->model->insertGetId(filterFields($info, $this->model));
        try {
            feature('tag.createTagBook', $info['jwtUserId'],  $info['target_type'] ?? group_users_str, $id, $info['tags_id']);
        } catch (\Exception $e) {
        }
        return $id;
    }
    public function update($id, $info)
    {
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('name', $info['name'])->exists();
        if ($is) tryFun('name_exists', info_err);
        $is = $this->model->where('id', $id)->update(filterFields($info, $this->model));
        if ($is) {
            try {
                feature('tag.createTagBook', $info['jwtUserId'], $info['target_type'] ?? group_users_str, $id, $info['tags_id']);
            } catch (\Exception $e) {
            }
        }
        return $is;
    }
    public function show($info)
    {
        $info =  $this->model->where('id', $info['id'])->where('uid', $info['jwtUserId'])->first()?->toArray();
        try {
            $tagMap = feature('tag.mapByTargets', $info['uid'],  group_users_str, [$info['id']]) ?? [];
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
            feature('tag.deleteTagBook',  group_users_str, $info['id']);
        } catch (\Exception $e) {
        }
        $list = $this->model->whereIn('id', $info['id'])->where('uid', $info['jwtUserId'])->get()->toArray();
        foreach ($list as $r) {
            if (is_file(public_path($r['file_url']))) {
                if (!unlink(public_path($r['file_url']))) {
                }
            }
        }
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['jwtUserId'])->delete();
    }

    public function tagMerge($info)
    {
        try {
            return feature('tag.tagMerge', $info['jwtUserId'],  group_users_str, $info['target_id'], $info['tags_id'], $info['merge_tags_id']);
        } catch (\Exception $e) {
            tryFun('tag_merge_error', info_err);
        }
    }
    public function getIdArr($uid, $idArr)
    {
        return $this->model->select(['id', 'name', 'total', 'file_url'])->whereIn('id', $idArr)->where('uid', $uid)->get()->toArray();
    }
}
