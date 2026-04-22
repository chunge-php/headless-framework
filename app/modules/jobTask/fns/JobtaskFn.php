<?php

namespace app\modules\jobTask\fns;

use app\modules\jobTask\model\JobDefinition;
use app\modules\tag\model\Tag;
use support\Db;

class  JobtaskFn
{
    protected $model;
    public function __construct(JobDefinition $model)
    {
        $this->model = $model;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model->select(['job_definitions.*', 'sms_prices.name as sms_price_name', 'sms_prices.price as sms_price', 'sms_prices.type as sms_prices_type'])->join('sms_prices', 'sms_prices.id', 'job_definitions.price_id')->where('job_definitions.uid', $info['jwtUserId']);
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('job_definitions.id', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
    public function create($info)
    {
        if (empty($info['price_id'])) {
            tryFun('price_not_exists', info_err);
        }
        $info['uid'] = $info['jwtUserId'];
        $meta_json =  [];
        if (!empty($info['meta_json']) && $info['kind'] == appointment_str) {
            $meta_json =   $info['meta_json'];
            if (isset($meta_json['start_date'])) {
                $info['next_days'] = $meta_json['start_date'];
            } else {
                tryFun('start_date_not_input', info_err);
            }
            if (isset($meta_json['start_time'])) {
                $info['next_time'] = $meta_json['start_time'];
            } else {
                tryFun('start_time_not_input', info_err);
            }
        }
        if ($info['kind'] == birthday_str) {
            $info['next_days'] = dayDate();
            $info['next_time'] = '09:00';
        }
        $info['meta_json'] = json_encode($meta_json, JSON_UNESCAPED_UNICODE);
        $info['run_status'] = $info['status'] == state_one ? state_two : state_zero;
        $info['updated_at'] = dayDateTime();
        $info['created_at'] = dayDateTime();
        return $this->model->insertGetId(filterFields($info, $this->model));
    }
    public function update($id, $info)
    {
        $is  = $this->model->where('id', $id)->exists();
        if (!$is) {
            tryFun('job_not_exists', info_err);
        }
        if (empty($info['price_id'])) {
            tryFun('price_not_exists', info_err);
        }
        $info['uid'] = $info['jwtUserId'];
        if (!empty($info['meta_json']) && $info['kind'] == appointment_str) {
            $meta_json =   $info['meta_json'];
            if (isset($meta_json['start_date'])) {
                $info['next_days'] = $meta_json['start_date'];
            } else {
                tryFun('start_date_not_input', info_err);
            }
            if (isset($meta_json['start_time'])) {
                $info['next_time'] = $meta_json['start_time'];
            } else {
                tryFun('start_time_not_input', info_err);
            }
            $meta_json =  json_encode($meta_json, JSON_UNESCAPED_UNICODE);
        } else {
            $meta_json = json_encode($info['meta_json'], JSON_UNESCAPED_UNICODE);
        }
        $info['meta_json'] = $meta_json;
        $info['run_total'] = 0;
        if ($info['kind'] == birthday_str) {
            $info['next_days'] = dayDate();
            $info['next_time'] = '09:00';
        }
        $info['run_status'] = $info['status'] == state_one ? state_two : state_zero;
        $info['updated_at'] = dayDateTime();

        return $this->model->where('id', $id)->update(filterFields($info, $this->model));
    }
    public function show($info)
    {
        $data =  $this->model
            ->select(['job_definitions.*', 'sms_prices.name as sms_price_name', 'sms_prices.price as sms_price', 'sms_prices.type as sms_prices_type'])->join('sms_prices', 'sms_prices.id', 'job_definitions.price_id')
            ->where('job_definitions.uid', $info['jwtUserId'])
            ->where('job_definitions.id', $info['id'])->first()?->toArray();
        if ($data) {
            $data['group_name'] = [];
            $data['tags_name'] = [];
            if (!empty($data['meta_json'])) {
                if (!empty($data['meta_json']['group_id'])) {
                    $data['group_name'] = Db::table('group_users')->select(['id', 'name', 'total'])->whereIn('id', $data['meta_json']['group_id'])->get()->toArray();
                }
                if (!empty($data['meta_json']['tags_id'])) {
                    $tags_id =  Tag::query()->select(['tags.id', 'tags.name', Db::raw('count(*) as total')])
                        ->join('tag_books', 'tags.id', 'tag_books.tags_id')
                        ->whereIn('tags.id', $data['meta_json']['tags_id'])
                        ->where('tag_books.target_type', addressbook_str)
                        ->where('tag_books.uid', $info['jwtUserId'])
                        ->groupBy('tags.id')
                        ->get()->toArray();
                    $tags_arr = array_column($tags_id, null, 'id');
                    foreach ($data['meta_json']['tags_id'] as $k => $r) {
                        if (isset($tags_arr[$r])) {
                            $data['tags_name'][] = [
                                'id' => $tags_arr[$r]['id'],
                                'name' => $tags_arr[$r]['name'],
                                'total' => $tags_arr[$r]['total']
                            ];
                        }
                    }
                }
            }
        }
        return $data;
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['jwtUserId'])->delete();
    }
    public function setStatus($id, $status, $uid)
    {
        return $this->model->where('id', $id)->where('uid', $uid)->update(['status' => $status, 'run_status' => $status==state_one ? state_two : state_zero]);
    }
    public function getKind($info)
    {
        return $this->model->select(['kind', Db::raw('count(*) as total')])->where('uid', $info['jwtUserId'])->groupBy('kind')->get()->toArray();
    }
    public function futureShow($info)
    {

        $data =  $this->model->where('uid', $info['jwtUserId'])->where('id', $info['id'])->first();
        $arr =  getAddDateTime(dayDate(), 30);
        $result = array_fill_keys($arr, []);
        if ($data) {
            $data = $data->toArray();

            if ($data['kind'] == birthday_str && $data['status'] == state_one) {
                $list = feature('user.addressbook.getDateUser', $data['uid'], $arr);
                $new_arr = [];
                foreach ($result as $k => $r) {
                    $ks =  substr($k, 5);
                    $new_arr[$ks] = [
                        'date' => $k,
                        'list' => []
                    ];
                }
                foreach ($list as $r) {
                    $ks =  substr($r['birthday'], 5);
                    if (isset($new_arr[$ks])) {
                        $r['time'] = $new_arr[$ks]['date'] . ' ' . $data['next_time'];
                        $new_arr[$ks]['list'][] = $r;
                    }
                }
                $res = [];
                foreach ($new_arr as $k => $v) {
                    if (!empty($v['list'])) {
                        $res[] = $v;
                    }
                }
                return $res;
            } elseif ($data['kind'] == appointment_str && $data['status'] == state_one && $data['run_status'] != state_zero) {
                $meta_json = $data['meta_json'];

                if (empty($meta_json)) return $result;
                if ($meta_json['end_number'] < dayDate() && $meta_json['end_type'] != 0) return $result;

                $list = [
                    'account_number' => $meta_json['account_number'],
                    'tags_id' => !empty($meta_json['tags_id']) ? feature('tag.getIdArr', $data['uid'], $meta_json['tags_id'], addressbook_str) : [],
                    'group_id' => !empty($meta_json['group_id']) ? feature('groupUser.getIdArr', $data['uid'], $meta_json['group_id']) : []
                ];
                $this->generateRunSchedule($data, $meta_json, $result, $list);
            }
        }
        $new = [];
        foreach ($result as  $k => $v) {
            if (!empty($v)) {
                $new[] = [
                    'date' => $k,
                    'list' => $v
                ];
            }
        }
        return  $new;
    }
    /**
     * 根据任务配置生成未来30天内的执行日期分布
     */
    public function generateRunSchedule(array $data, array $meta_json, &$result, $list)
    {
        // 2. 基础数据
        $nextDays = $data['next_days'] ?? ''; // 下次执行日期
        $nextTime = $data['next_time'] ?? ''; // 下次执行时间 (HH:ii)
        $run_total = (int)($data['run_total'] ?? 0); // 已执行总次数

        $repeatType   = isset($meta_json['repeat_type']) ? (int)$meta_json['repeat_type'] : 0; // 0不重复 1小时,2天,3周,4月,5年
        $repeatNumber = isset($meta_json['repeat_number']) ? max(1, (int)$meta_json['repeat_number']) : 1;
        $endType      = isset($meta_json['end_type']) ? (int)$meta_json['end_type'] : 0; // 0不结束,1 n次后结束,2指定日期
        $endNumber    = $meta_json['end_number'] ?? 0; // n次 或 指定日期字符串
        // 3. 没有下一次执行日期则直接返回
        if (!$nextDays) return $result;

        // 4. 起始时间点
        $start = strtotime($nextDays . ' ' . ($nextTime ?: '00:00:00'));

        $now = strtotime(dayDate());
        $endDate = strtotime('+30 days', $now); // 仅生成30天内

        // 5. 计算重复的间隔（秒数或偏移单位）
        $interval = match ($repeatType) {
            1 => 3600 * $repeatNumber,        // 小时
            2 => 86400 * $repeatNumber,       // 天
            3 => 604800 * $repeatNumber,      // 周
            default => 0
        };

        $count = 0;
        $current = $start;
        $day_date = date('Y-m-d');
        $day_time = date('H:i:s');
        while (true) {
            $date = date('Y-m-d', $current);
            $time = date('H:i:s', $current);
            // 超出30天范围 -> 停止
            if ($current > $endDate) break;
            // 记录当前时间点
            if (isset($result[$date]) && $date >= $day_date) {
                if ($date == $day_date) {
                    if ($time >= $day_time) {
                        $list['time'] = $date . ' ' . $time;

                        $result[$date][] = $list;
                    }
                } else {
                    $list['time'] = $date . ' ' . $time;
                    $result[$date][] = $list;
                }
            }

            // 判断结束条件
            $count++;
            if ($endType == 1 && $count >= (int)$endNumber) break; // N次后结束
            if ($endType == 2 && strtotime($date) >= strtotime($endNumber)) break; // 到指定日期结束
            if ($repeatType == 0) break; // 不重复

            // 下一次执行时间
            if (in_array($repeatType, [1, 2, 3])) {
                $current += $interval;
            } elseif ($repeatType == 4) {
                $current = strtotime("+{$repeatNumber} month", $current);
            } elseif ($repeatType == 5) {
                $current = strtotime("+{$repeatNumber} year", $current);
            }
        }
    }
}
