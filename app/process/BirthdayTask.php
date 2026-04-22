<?php

namespace app\process;

use support\Db;
use Workerman\Timer;

class BirthdayTask
{
    public function onWorkerStart()
    {
        colorText('会员生日任务'.dayDateTime(), 'green');
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
            ->where('job_definitions.next_days', '<=',dayDate())
            ->where('job_definitions.kind', birthday_str)
            ->orderBy('job_definitions.id');
        $query->chunk(100, function ($batch) {
            foreach ($batch as $item) {
                // 'sms.body.create' => '创建短信发送任务'
                if ($item->next_time <= date('H:i')) {
                    $data = [
                        "price_id" => $item->price_id,
                        "content" => $item->content,
                        "account_number" => "all",
                        'jwtUserId' => $item->uid,
                        'name' => $item->name . '~',
                        'scope_id' => $item->id,
                        'scope' => birthday_str,
                        'subscribe_type'=>state_one
                    ];
                    feature('sms.body.create', $data);
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    Db::table('job_definitions')->where('id', $item->id)->update(['run_status' => state_two, 'run_total' => $item->run_total + 1, 'next_days' => $tomorrow]);
                }
            }
        });
    }
}
