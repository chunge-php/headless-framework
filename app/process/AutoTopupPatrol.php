<?php

namespace app\process;

use app\modules\myclass\MailgunService;
use Illuminate\Support\Carbon;
use support\Db;
use Webman\RedisQueue\Redis as RedisQueueRedis;
use support\Redis;
use Workerman\Timer;

class AutoTopupPatrol
{
    public function onWorkerStart()
    {
        colorText('自动扣款启动'.dayDateTime(), 'green');

        // 每 60 秒巡逻一次
        Timer::add(60, function () {

            $this->startFn();
        });
    }
    public function startFn()
    {
        $cutoffTime = time() - 180;
        // 拉启用规则
        $query = Db::table('auto_topup_rules')
            ->where('enabled', state_one)
            // 可选：把冷却判断下推到 SQL，减少 PHP 侧判断
            ->where(function ($q) use ($cutoffTime) {
                $q->whereNull('last_run_at')
                    ->orWhere('last_run_at', '<=', date('Y-m-d H:i:s', $cutoffTime));
            })
            ->orderBy('id');
        $years = date('Y');
        $months = date('m');
        $days = date('d');
        // 分块处理，每块 200 条，按 id 递增扫描
        $query->chunkById(200, function ($batch) use ($days, $months, $years) {
            $now = dayDateTime();
            foreach ($batch as $r) {
                // 冷却：last_run_at + cooldown_sec 之内就跳过

                if ($r->last_run_at && (time() - strtotime($r->last_run_at)) <= (int)$r->cooldown_sec) {
                    continue;
                }
                Db::beginTransaction();
                try {

                    // 锁钱包，拿最新余额
                    $wallet = Db::table('wallets')->where('id', $r->wallets_id)->lockForUpdate()->first();
                    if (!$wallet) {
                        Db::rollBack();
                        continue;
                    }

                    $balance = $wallet->balance;
                    if ($r->reminder_status == state_zero && $balance <= $r->reminder_price) {
                        //发送提醒
                        Db::table('auto_topup_rules')->where('id',$r->id)->update(['reminder_status' => state_one]);
                        $user = Db::table('users')->where('id', $r->uid)->first();
                        $first = trim($user->first_name ?? '');
                        $last  = trim($user->last_name ?? '');
                        $username = trim($first . ' ' . $last) ?: 'User';
                        $tplPath  = base_path('resource/stubs/reminder_price.html'); // 无需前导斜杠
                        $template = @file_get_contents($tplPath);
                        if ($template === false) {
                            // 简易兜底
                            $template = "Hi {{customer_name}}, as of {{timestamp_et}} (ET) your balance {{balance_formatted}} is below {{threshold_formatted}}.";
                        }
                        // ④ 变量准备（纽约时区 & 金额格式）
                        $timestampET = Carbon::now('America/New_York')->format('M j, Y g:i A');
                        $fmt = fn($v) => '$' . number_format((float) $v, 2);
                        $vars = [
                            '{{customer_name}}'        => $username,
                            '{{timestamp_et}}'         => $timestampET,
                            '{{balance_formatted}}'    => $fmt($balance),
                            '{{threshold_formatted}}'  => $fmt($r->reminder_price),
                            '{{account_id}}'           => (string) ($wallet->account_sid ?? ''),
                            '{{add_funds_url}}'        => (string) config('app.add_funds_url'),
                            '{{brand_name}}'           => (string) config('app.name', 'Your Company'),
                            '{{year}}'                 => date('Y'),
                            '{{company_address_line}}' => (string) config('app.company_address_line', ''),
                            // 若模板里还有 {{account_name}} / {{manage_alerts_url}} 等，也在这里补上
                        ];

                        // ⑤ 一次性替换（比多次 str_replace 稳）
                        $body = strtr($template, $vars);
                        // ⑥ 收件人
                        $account_number = Db::table('user_identities')
                            ->where('uid', $r->uid)
                            ->value('account_number');
                        // ⑦ 发送
                        try {
                            (new MailgunService())->sendEmail($account_number, 'Low balance alert', $body);
                        } catch (\Exception $e) {
                        }
                    }
                    if ($balance >= $r->threshold_cents) {
                        // 不需要加钱，仅更新检查时间与updated_at
                        Db::table('auto_topup_rules')->where('id', $r->id)->update([
                            'last_run_at' => $now,
                            'updated_at' => $now
                        ]);
                        Db::commit();
                        continue;
                    }

                    // 需要加钱
                    $amount = $r->topup_amount_cents;
                    $info = [
                        'uid' => $r->uid,
                        'wallet_id' => $wallet->id,
                        'amount' => $amount,
                        'now' => $now,
                        'today_count' => $r->today_count,
                        'rule_id' => $r->id,
                        'before_balance_cents' => $balance,
                        'days' => $days,
                        'months' => $months,
                        'years' => $years
                    ];
                    Db::commit();
                    RedisQueueRedis::send('wallet-topup-balance', $info);
                } catch (\Throwable $e) {
                    Db::rollBack();
                    debugMessage('AutoTopup error rule=' . $r->id . ' ' . $e->getMessage(),'自动扣款');

                    // 失败也记一下日志（可选）
                    try {
                        Db::table('wallet_topup_logs')->insert([
                            'uid' => $r->uid,
                            'amount_cents' => (int)$r->topup_amount_cents,
                            'before_balance_cents' => isset($balance) ? $balance : 0,
                            'after_balance_cents' => isset($newBalance) ? $newBalance : 0,
                            'status' => state_two,
                            'response_data' => json_encode(['error' => 'exception: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    } catch (\Throwable $ex) {
                    }
                }
            }
        }, 'id');
    }
}
