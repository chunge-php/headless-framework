<?php

namespace app\process;

use support\Db;
use Webman\RedisQueue\Redis as RedisQueueRedis;
use support\Redis;
use Workerman\Timer;

class SmsBillTask
{
    public function onWorkerStart()
    {
        colorText('短信统计任务'.dayDateTime(), 'green');
        // 每 60 秒巡逻一次
        Timer::add(60, function () {
            $this->startFn();
        });
    }
    public function startFn()
    {
        $years = date('Y');
        $months = date('m');
        // 拉启用规则
        $query = Db::table('users')
            ->where('state', state_one)
            ->orderBy('id');
        $query->chunkById(1000, function ($batch) use ($years, $months) {

            foreach ($batch as $r) {
                $sms_bills_id =  Db::table('sms_bills')
                    ->where('uid', $r->id)
                    ->where('years', $years)
                    ->where('months', $months)->value('id') ?? 0;
                $sms_list = Db::table('sms_batch_lots')
                    ->select([Db::raw('SUM(total_money) as total_money'), Db::raw('SUM(consume_number) as consume_number'), 'send_type'])
                    ->where('status', state_one)
                    ->where('years', $years)
                    ->where('months', $months)
                    ->where('uid', $r->id)
                    ->groupBy('send_type')->get();
                if ($sms_list->isEmpty()) continue;
                $total_price = 0;
                $sms_price = 0;
                $mms_price = 0;
                $email_price = 0;
                $total_sms = 0;
                $total_mms = 0;
                $total_email = 0;
                foreach ($sms_list as $k => $v) {
                    if ($v->send_type == state_zero) {
                        $total_price += $v->total_money;
                        $sms_price = $v->total_money;
                        $total_sms = $v->consume_number;
                    } elseif ($v->send_type == state_one) {
                        $total_price += $v->total_money;
                        $mms_price = $v->total_money;
                        $total_mms = $v->consume_number;
                    } elseif ($v->send_type == state_two) {
                        $total_price += $v->total_money;
                        $email_price = $v->total_money;
                        $total_email = $v->consume_number;
                    }
                }
                $data = [
                    'uid' => $r->id,
                    'total_price' => money_to_decimal($total_price),
                    'sms_price' => money_to_decimal($sms_price),
                    'mms_price' => money_to_decimal($mms_price),
                    'email_price' => money_to_decimal($email_price),
                    'total_sms' => (int)$total_sms,
                    'total_mms' => (int)$total_mms,
                    'total_email' => (int)$total_email,
                    'updated_at' => dayDateTime()
                ];
                if ($sms_bills_id) {
                    Db::table('sms_bills')->where('id', $sms_bills_id)->update($data);
                } else {
                    $data['years'] = $years;
                    $data['months'] = $months;
                    $data['invoice_number'] = 'FVPJNM-' . $years . '-' . $months;
                    Db::table('sms_bills')->where('id', $sms_bills_id)->insert($data);
                }
            }
        }, 'id');
    }
}
