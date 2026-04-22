<?php

namespace app\modules\game\fns;

use app\modules\game\model\GameList;
use app\modules\game\model\GameLog;
use app\modules\game\model\GameUser;
use app\modules\sms\body\model\SmsChannelLog;
use support\Db;
use support\Redis;
use Webman\RedisQueue\Redis as RedisQueueRedis;

class  GameFn
{
    protected $GameList;
    protected $gameUser;
    protected $gameLog;
    protected $smsChannelLog;
    public function __construct(GameList $GameList, GameUser $gameUser, GameLog $gameLog,SmsChannelLog $smsChannelLog)
    {
        $this->GameList = $GameList;
        $this->gameUser = $gameUser;
        $this->gameLog = $gameLog;
        $this->smsChannelLog = $smsChannelLog;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->GameList;
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
    public function getIndexLog($info, $limit = 20, $offset = 1): array
    {
        $where = $this->gameLog->where('uid', $info['jwtUserId'])
            ->when(!empty($info['type']), function ($query) use ($info) {
                $query->where('type', $info['type']);
            })->when(!empty($info['to']), function ($query) use ($info) {
                $query->where('to', 'like', '%' . $info['to'] . '%');
            })->when(!empty($info['start_time']) && !empty($info['end_time']), function ($query) use ($info) {
                if (strlen($info['start_time']) == 10) {
                    $info['start_time'] .= ' 00:00:00';
                }
                if (strlen($info['end_time']) == 10) {
                    $info['end_time'] .= ' 23:59:59';
                }
                $query->whereBetween('created_at', [$info['start_time'], $info['end_time']]);
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
            return ['total' => $total, 'list' => $list];
        }
    }
    public function getParticipantIndex($info, $limit = 20, $offset = 1): array
    {
        $where = $this->smsChannelLog
        ->select(['id','to','created_at'])
        ->when(!empty($info['to']), function ($query) use ($info) {
            $query->where('to', 'like', '%' . $info['to'] . '%');
        })
        ->where('uid', $info['uid'])->where('channel_type', state_three)->when(!empty($info['start_time']) && !empty($info['end_time']), function ($query) use ($info) {
            if (strlen($info['start_time']) == 10) {
                $info['start_time'] .= ' 00:00:00';
            }
            if (strlen($info['end_time']) == 10) {
                $info['end_time'] .= ' 23:59:59';
            }
            $query->whereBetween('created_at', [$info['start_time'], $info['end_time']]);
        });
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
    public function create($info)
    {


        $options = !empty($info['options']) ? json_encode($info['options'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE);
        $info['options'] = $options;
        return $this->GameList->insertGetId(filterFields($info, $this->GameList));
    }
    public function update($id, $info)
    {

        $options = !empty($info['options']) ? json_encode($info['options'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE);
        $info['options'] = $options;
        return $this->GameList->where('id', $id)->update(filterFields($info, $this->GameList));
    }
    public function show($id)
    {
        return $this->GameList->where('id', $id)->first()?->toArray();
    }
    public function apiShow($code)
    {
        return $this->gameUser->where('code', $code)->first()?->toArray();
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->GameList->whereIn('id', $info['id'])->delete();
    }
    public function setMy($info)
    {
        $data =  $this->gameUser->where('uid', $info['uid'])->first();
        $info['options'] = !empty($info['options']) ? json_encode($info['options'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE);

        $info['pre'] = !empty($info['pre']) ? json_encode($info['pre'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE);
        $info['switch'] = !empty($info['switch']) ? json_encode($info['switch'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE);

        if ($data) {
            if (isset($info['code'])) {
                unset($info['code']);
            }
            return   $this->gameUser->where('uid', $info['uid'])->update(filterFields($info, $this->gameUser));
        } else {
            $info['code'] = $this->generateRandomSixCharString();
            return  $this->gameUser->insert(filterFields($info, $this->gameUser));
        }
    }
    public function getMy($uid)
    {
        $data =  $this->gameUser->where('uid', $uid)->first()?->toArray();
        if ($data) {
            return $data;
        } else {
            $info['code'] = $this->generateRandomSixCharString();
            $info['uid'] = $uid;
            $id =   $this->gameUser->insertGetId(filterFields($info, $this->gameUser));
            return  $this->gameUser->where('id', $id)->first()?->toArray();
        }
    }
    public function sendSmsResult($to, $uid, $body)
    {
        $price_id =   feature('sms.config.getPriceId', state_zero);
        $data = [
            "price_id" => $price_id,
            "content" => $body,
            "account_number" => [$to],
            'group_id' => [],
            'tags_id' =>  [],
            'mms_url' =>  '',
            'channel_type' => state_three,
            'subject' =>  '',
            'jwtUserId' => $uid,
            'name' =>  'Game~result',
            'subscribe_type' => state_zero
        ];
        feature('sms.body.create', $data);
        return true;
    }
    private function generateRandomSixCharString($model = 'model')
    {
        // 定义可用的字符集，包括大写字母、小写字母和数字
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';

        // 初始化一个空的结果字符串
        $randomString = '';


        if ($model == 'model') {
            // 随机选择6个字符
            for ($i = 0; $i < 6; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }
            $is  = $this->gameUser->where('code', $randomString)->exists();
            if ($is) {
                return $this->generateRandomSixCharString();
            }
        } elseif ($model == 'redis') {
            // 随机选择6个字符
            $characters = '0123456789';
            for ($i = 0; $i < 6; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }
        }
        return $randomString;
    }
    public function sendCode($to, $uid)
    {
        $code = $this->generateRandomSixCharString('redis');
        $game_code  =  $this->gameUser->select(['code', 'type'])->where('uid', $uid)->first();
        if (empty($game_code)) tryFun('game_not_exist');
        // $type = $game_code->type;
        // $str_code = config('app.game_url') . "{$type}/{$game_code->code}";
        $str = "Your verification code: [{$code}].";
        $price_id =   feature('sms.config.getPriceId', state_zero);
        $data = [
            "price_id" => $price_id,
            "content" => $str,
            "account_number" => [$to],
            'group_id' => [],
            'tags_id' =>  [],
            'mms_url' =>  '',
            'channel_type' => state_three,
            'subject' =>  '',
            'jwtUserId' => $uid,
            'name' =>  'Game~',
            'subscribe_type' => state_zero
        ];

        $jobid =  $this->setJobId($to, $code, $game_code->code);
        feature('sms.body.create', $data);
        return $jobid;
    }
    public function verCode($code, $to)
    {
        $jobId = $this->getJobIdInfo($to);
        if (empty($jobId)) {
            return tryFun('verify_code_not');
        }
        if (isset($jobId['status']) &&  $jobId['status'] == 'pending' && $code  == $jobId['code']) {
            $jobId_md5 = $jobId['jobId'];
            Redis::setex("game:{$jobId_md5}", 120, json_encode(['status' => 'success', 'to' => $jobId['to'], 'game_code' => $jobId['game_code'], 'jobId' => $jobId['jobId']], JSON_UNESCAPED_UNICODE));
            return true;
        } else {
            return tryFun('verify_code_not');
        }
    }
    public function getJobResult($jobId)
    {
        $data =  Redis::get("game:{$jobId}");
        if (!empty($data)) {
            $res =  json_decode($data, true);
            if (isset($res['status']) && $res['status'] == 'success') {
                $game_info =  $this->gameUser->select(['id', 'code', 'uid', 'type', 'body', 'pre'])->where('code', $res['game_code'])->first()?->toArray();
                if ($game_info) {
                    if (empty($game_info['pre'])) tryFun('rate_not_error');
                    $result =  $this->lottery($game_info['pre']);
                    $this->gameLog->insert([
                        'uid' => $game_info['uid'],
                        'to' => $res['to'],
                        'result' => $result['value'] ?? '未中奖',
                        'body' => $game_info['body'],
                        'type' => $game_info['type'],
                    ]);
                    Redis::setex("game:{$jobId}", 15, json_encode(['status' => 'end', 'jobId' => $jobId], JSON_UNESCAPED_UNICODE));
                    $res['result'] = $result;
                    unset($res['to'], $res['game_code']);
                    return $res;
                } else {
                    Redis::del("game:{$jobId}");
                    return tryFun('game_not_exist');
                }
            } else {
                if (isset($res['status'])) {
                    return ['status' => $res['status'],  'jobId' => $jobId, 'code' => $res['code']];
                } else {
                    return ['status' => 'end',  'jobId' => $jobId];
                }
            }
        } else {
            return ['status' => 'end',  'jobId' => $jobId];
        }
    }
    public function getJobIdInfo($to)
    {
        $jobId = md5($to);
        $data = Redis::get("game:{$jobId}");
        if ($data) {
            return json_decode($data, true);
        }
        return false;
    }
    public function setJobId($to, $code, $game_code)
    {
        $jobId = md5($to);
        // 判断是否在线
        Redis::setex("game:{$jobId}", 300, json_encode(['status' => 'pending', 'game_code' => $game_code, 'jobId' => $jobId,  'code' => $code, 'to' => $to], JSON_UNESCAPED_UNICODE));
        // 生成 jobId
        return $jobId;
    }
    private function rateToPoints($rate): int
    {
        $s = trim((string)$rate);

        // 允许：整数 / 1~3位小数；不允许负数/空/科学计数法/超过3位
        if (!preg_match('/^\d+(?:\.\d{1,3})?$/', $s)) {
            tryFun('rate_not_error');
        }

        [$intPart, $fracPart] = array_pad(explode('.', $s, 2), 2, '');
        $fracPart = str_pad($fracPart, 3, '0', STR_PAD_RIGHT); // 补足3位

        $points = ((int)$intPart) * 1000 + (int)$fracPart;

        // 单个 rate 不允许超过 100.000（可选，但建议加）
        if ($points > 100000) {
            tryFun('rate_not_error');
        }

        return $points;
    }
    private function lottery(array $prizes): array
    {
        // 1) 预处理：计算每项权重点位，并求和
        $items = [];
        $sumPoints = 0;

        foreach ($prizes as $key => $prize) {
            $points = $this->rateToPoints($prize['rate'] ?? null); // 允许最多3位小数

            // 允许 0（表示永不中），但不参与累积区间
            if ($points > 0) {
                $items[] = [
                    'key' => $key,
                    'prize' => $prize,
                    'points' => $points,
                ];
                $sumPoints += $points;
            }
        }

        // 2) 校验：总权重必须 > 0
        if ($sumPoints <= 0) {
            throw new \Exception(trans('rate_not_error'));
        }

        // 3) 按权重抽取：随机落点在 1..sumPoints
        $rand = mt_rand(1, $sumPoints);
        // 若你需要更强随机（防刷奖），建议用：$rand = random_int(1, $sumPoints);

        $cumulative = 0;
        foreach ($items as $item) {
            $cumulative += $item['points'];
            if ($rand <= $cumulative) {
                $result = $item['prize'];
                $result['key'] = $item['key'];
                unset($result['rate']);
                return $result;
            }
        }

        // 理论上不会到这里
        return [
            'value' => '未中奖',
            'key' => -1,
        ];
    }
}
