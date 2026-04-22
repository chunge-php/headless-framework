<?php

namespace app\process;

use support\Db;
use Workerman\Timer;

class AppointmentTask
{
    public function onWorkerStart()
    {
        colorText('预约任务' . dayDateTime(), 'green');
        // 每 60 秒巡逻一次
        Timer::add(60, function () {
            $this->startFn();
        });
    }

    public function startFn()
    {
        $query = Db::table('job_definitions')
            ->select(['job_definitions.*'])
            ->join('users', 'users.id', 'job_definitions.uid')
            ->where('users.state', state_one)
            ->where('job_definitions.status', state_one)
            ->where('job_definitions.run_status', state_two)
            ->where('job_definitions.next_days', '<=', dayDate())
            ->where('job_definitions.kind', appointment_str)
            ->orderBy('job_definitions.id');

        $query->chunk(100, function ($batch) {
            foreach ($batch as $item) {
                try {
                    if(empty($item->next_time)){
                        continue;
                    }
                    if ($item->next_time <= date('H:i')) {
                        $meta = json_decode($item->meta_json ?? '{}', true) ?: [];
                        if (empty($meta)) continue;
                        $data = [
                            "price_id" => $item->price_id,
                            "content" => $item->content,
                            "account_number" => $meta['account_number'] ?? [],
                            'group_id' => $meta['group_id'] ?? [],
                            'tags_id' => $meta['tags_id'] ?? [],
                            'mms_url' => $meta['mms_url'] ?? '',
                            'subject' => $meta['subject'] ?? '',
                            'jwtUserId' => $item->uid,
                            'name' => $item->name . '~',
                            'scope_id' => $item->id,
                            'scope' => appointment_str,
                            'subscribe_type' => state_two
                        ];
                        feature('sms.body.create', $data);

                        // 传递当前执行次数给 computeNextRun
                        [$nextDate, $nextTime, $metaUpdated] = $this->computeNextRun(
                            $item->next_days,
                            $item->next_time,
                            $meta,
                            (int)$item->run_total + 1  // 当前执行次数（执行后会是+1）
                        );

                        // 成功：累计 +1，写下一次
                        Db::table('job_definitions')
                            ->where('id', $item->id)
                            ->update([
                                'run_total'  => $item->run_total + 1,
                                'run_status' => $nextDate ? state_two : state_zero, // 2待执行 / 0结束
                                'next_days'  => $nextDate,
                                'next_time'  => $nextTime,
                                'meta_json'  => json_encode($metaUpdated, JSON_UNESCAPED_UNICODE),
                            ]);
                    }
                } catch (\Exception $e) {
                    debugMessage([$e->getLine(), $e->getMessage()], '预约任务异常');
                }
            }
        });
    }

    public function computeNextRun(
        string $curDate,
        string $curTime,
        array  $meta,
        int    $currentRunTotal = 0
    ): array {
        // 重复类型：0不重复 1小时 2天 3周 4月 5年
        $repeatType   = (int)($meta['repeat_type'] ?? 0);
        $repeatNumber = max(1, (int)($meta['repeat_number'] ?? 1));

        // 结束条件：0不结束 1按次数 2按日期
        $endType   = (int)($meta['end_type'] ?? 0);
        $endNumber = $meta['end_number'] ?? 0;

        if ($repeatType === 0) {
            // 不重复：直接结束
            return [null, null, $meta];
        }

        // 当前执行点时间戳
        $baseTs = strtotime($curDate . ' ' . $curTime);
        if ($baseTs === false) {
            return [null, null, $meta];
        }

        // 计算下一个执行时间戳
        switch ($repeatType) {
            case 1: // 小时
                $nextTs = $baseTs + $repeatNumber * 3600;
                break;

            case 2: // 天
                $nextTs = strtotime("+{$repeatNumber} day", $baseTs);
                break;

            case 3: // 周
                $nextTs = strtotime("+{$repeatNumber} week", $baseTs);
                break;

            case 4: // 月
                $nextTs = strtotime("+{$repeatNumber} month", $baseTs);
                break;

            case 5: // 年
                $nextTs = strtotime("+{$repeatNumber} year", $baseTs);
                break;

            default:
                return [null, null, $meta];
        }

        if (!$nextTs) {
            return [null, null, $meta];
        }

        $nextDate = date('Y-m-d', $nextTs);
        $nextTime = date('H:i',   $nextTs);

        /**
         * ========= 结束条件判断 =========
         */

        // 1️⃣ 按次数结束
        if ($endType === 1) {
            $endCount = (int)$endNumber;
            if ($endCount > 0 && $currentRunTotal >= $endCount) {
                return [null, null, $meta];
            }
        }

        // 2️⃣ 按日期结束
        if ($endType === 2 && is_string($endNumber) && $endNumber !== '') {
            // 只比较日期即可
            if ($nextDate > $endNumber) {
                return [null, null, $meta];
            }
        }

        // 0️⃣ 无限重复：不处理

        return [$nextDate, $nextTime, $meta];
    }
}
