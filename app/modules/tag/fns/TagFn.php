<?php

namespace app\modules\tag\fns;

use app\modules\tag\model\Tag;
use app\modules\tag\model\TagBook;
use support\Db;

class  TagFn
{
    protected $model;
    protected $tagBook;
    public function __construct(Tag $tag, TagBook $tagBook)
    {
        $this->model = $tag;
        $this->tagBook = $tagBook;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model->where('uid', $info['jwtUserId'])
            ->where('target_type', $info['target_type'])
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
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('target_type', $info['target_type'])->where('name', $info['name'])->exists();
        if ($is) tryFun('tag_name_exists', info_err);
        $info['uid'] = $info['jwtUserId'];
        return $this->model->insertGetId(filterFields($info, $this->model));
    }
    public function update($id, $info)
    {
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('target_type', $info['target_type'])->where('name', $info['name'])->exists();
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
    public function deleteTagBook($target_type, $target_id)
    {
        return $this->tagBook->where('target_type', $target_type)->whereIn('target_id', $target_id)->delete();
    }

    public function mapByTargets(string|int $uid, string $targetType, array $targetIds): array
    {
        if (!$targetIds) return [];
        $rows = $this->tagBook
            ->join('tags as t', 'tag_books.tags_id', '=', 't.id')
            ->where('tag_books.uid', $uid)
            ->where('tag_books.target_type', $targetType)
            ->whereIn('tag_books.target_id', $targetIds)
            ->select('tag_books.target_id', 't.id as tags_id', 't.name', 't.colour')
            ->get()->toArray();
        $map = [];
        foreach ($rows as $r) {
            $tid = (int)$r['target_id'];
            $map[$tid][] = ['id' => (int)$r['tags_id'], 'name' => $r['name'], 'colour' => $r['colour']];
        }
        return $map;
    }
    public  function createTagBook($uid, $target_type, $target_id, $tags_id)
    {
        $this->tagBook->where('uid', $uid)->where('target_type', $target_type)->where('target_id', $target_id)->delete();
        if ($tags_id) {
            $data = [];
            foreach ($tags_id as $v) {
                $data[] = ['uid' => $uid, 'target_type' => $target_type, 'target_id' => $target_id, 'tags_id' => $v];
            }
            $this->tagBook->insert($data);
        }
    }
    public function createTagBookBatch($uid, $target_type, $target_id, $tags_id)
    {
        foreach ($target_id as $id) {
            $this->createTagBook($uid, $target_type, $id, $tags_id);
        }
    }
    public function getNameIndex($info, $limit = 20, $offset = 1)
    {
        $where = $this->model
            ->select(['id', 'name', 'colour'])
            ->withCount(['tagBook'])
            ->where('uid', $info['jwtUserId'])
            ->where('target_type', $info['target_type'])
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
    public function tagMerge($uid, $target_type, $target_id, $tags_id, $merge_tags_id)
    {
        try {
            if (!is_array($tags_id)) {
                $tags_id = explode(',', $tags_id);
            }

            $merge_tags_id = array_unique(array_merge($tags_id, $merge_tags_id));
            $raw_list  = $this->tagBook
                ->select(['tags_id', 'target_id'])
                ->where('uid', $uid)->where('target_type', $target_type)
                ->when($target_id != 'all', function ($query) use ($target_id) {
                    return $query->whereIn('target_id', $target_id);
                })
                ->when(!empty($merge_tags_id) && $target_id == 'all', function ($query) use ($merge_tags_id) {
                    return $query->whereIn('tags_id', $merge_tags_id);
                })
                ->get()
                ->toArray();
            $res_data = [];
            $target_id_arr = [];
            foreach ($raw_list as $r) {
                $target_id_arr[$r['target_id']] = $r['target_id'];
                if (isset($res_data[$r['target_id']])) {
                    $res_data[$r['target_id']][] = $r['tags_id'];
                } else {
                    $res_data[$r['target_id']] = [$r['tags_id']];
                }
            }
            if (empty($target_id_arr)) {
                if ($target_id != 'all') {
                    foreach ($target_id as $k) {
                        foreach ($tags_id as $v) {
                            if (!empty($v)) {
                                $data[] = [
                                    'uid' => $uid,
                                    'tags_id' => $v,
                                    'target_type' => $target_type,
                                    'target_id' => $k,
                                ];
                            }
                        }
                    }
                }
                if (empty($data)) return true;
                return  $this->tagBook->insert($data);
            };
            $data = [];
            foreach ($res_data as $k => $v) {
                foreach ($tags_id as $vs) {
                    if (!in_array($vs, $v)) {
                        $data[] = [
                            'uid' => $uid,
                            'tags_id' => $vs,
                            'target_type' => $target_type,
                            'target_id' => $k,
                        ];
                    }
                }
            }
            if (empty($data)) return true;
            return  $this->tagBook->insert($data);
        } catch (\Exception $e) {
            debugMessage([$e->getLine(), $e->getMessage(), $e->getFile()], 'tagMerge');
            return false;
        }
    }
    public function getIdArr($uid, $idArr, $target_type)
    {
        $list =  $this->model->select(['id', 'name', 'colour'])->where('uid', $uid)->whereIn('id', $idArr)->get()->toArray();
        $ids = array_map(fn($r) => (int)$r['id'], $list);
        $total_list = $this->tagBook->select(['tags_id', Db::raw('count(*) as total')])->where('uid', $uid)->whereIn('tags_id', $ids)
            ->where('target_type', $target_type)->groupBy('tags_id')->pluck('total', 'tags_id')->toArray();
        $list = array_column($list, null, 'id');
        foreach ($total_list as $k => $v) {
            $list[$k]['total'] = $v;
        }
        return array_values($list);
    }
}
