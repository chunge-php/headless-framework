<?php

namespace app\queue\redis\wallet;

use support\Db;
use support\Redis;
use Webman\RedisQueue\Consumer;

class TopupBalance implements Consumer
{
    public $queue = 'wallet-topup-balance';
    public $connection = 'default'; // 连接名，对应 config/redis_queue.php 文件中的连接
    public function consume($data)
    {
        colorText($data['uid'] . '充值余额队列', 'success');
        $this->startFn($data);
    }
    public function  startFn($data)
    {
        $data['auto_topup_state'] = isset($data['auto_topup_state']) ? $data['auto_topup_state'] : true;

        $now      = $data['now'] ?? date('Y-m-d H:i:s');
        $uid      = (int)($data['uid'] ?? 0);
        $reids_uid = 'b:' . $uid;
        Db::beginTransaction();
        try {
            $amount = price_round2($data['amount']);
            $before_balance_cents  = price_round2($data['before_balance_cents']);
            $after_balance_cents = price_round2($amount + $before_balance_cents);
            $card_info =  Db::table('cards')->select(['id', 'token', 'expiry'])->where('uid', $uid)->where('status', state_one)->where('default_state', state_one)->first();
            if (!$card_info) {
                colorText('没有可用的信用卡', 'red');
                throw new \RuntimeException('No available credit card.', card_not);
            }
            //向信用卡发起请求
            $consume_money = toNum($amount);
            $auth_data = [
                'account' => $card_info->token,
                'expiry' => $card_info->expiry,
                'capture' => 'Y',
                'amount' => $consume_money,
            ];
            //正式环境开启
            if (!config('app.debugs')) {
                $request_data = feature('myclass.CardPointeGateway.authPayment', $auth_data);
            } else {
                $request_data = [
                    'respcode' => '000',
                    'retref' => '176798010225',
                    'authcode' => '00464G',
                    'resptext' => 'success'
                ];
            }
            $code = feature('user.wallet.nextBillNo', 'C', 6, 'America/New_York');
            $log_data  = [
                'uid' => $data['uid'],
                'code' => $code,
                'cards_id' => $card_info->id,
                'amount_cents' => $amount,
                'before_balance_cents' => $before_balance_cents,
                'after_balance_cents' => $after_balance_cents,
                'status' => state_one,
                'years' => $data['years'],
                'months' => $data['months'],
                'days' => $data['days'],
                'created_at' => $now
            ];
            $today_count = (int)$data['today_count'] + 1;
            $rules_data = [
                'reminder_status'=> state_zero,
                'today_count' => $today_count,
                'last_run_at' => $now,
                'updated_at' => $now
            ];
            $bill = [
                'uid' => $uid,
                'code' => $code,
                'total_money' => $amount,
                'balance' => $after_balance_cents,
                'bill_type' => state_one,
                'years' => $data['years'],
                'months' => $data['months'],
                'days' => $data['days'],
                'created_at' => $now,
            ];
            if (isset($request_data['retref']) && isset($request_data['respcode']) && ($request_data['respcode'] == '000' || $request_data['respcode'] == '00' || $request_data['respcode'] == '0')) {
                // 1) 钱包加钱
                Db::table('wallets')->where('id', $data['wallet_id'])->increment('balance', $amount, ['updated_at' => $data['now']]);
                Db::commit();
                //redis更新
                if (Redis::hGetAll($reids_uid)) {
                    Redis::hIncrByFloat($reids_uid, 'balance', +$amount);
                } else {
                    feature('user.wallet.getBalance', $uid);
                }
                // 2) 记日志
                $bill['batch_id'] = Db::table('wallet_topup_logs')->insertGetId($log_data);
                // 3) 更新规则统计 + 冷却时间
                if ($data['auto_topup_state']) {
                    Db::table('auto_topup_rules')->where('id', $data['rule_id'])->update($rules_data);
                }
                Db::table('bills')->insert($bill);
            } else {
                // 2) 记日志
                $log_data['status'] = state_two;
                Db::table('wallet_topup_logs')->insert($log_data);
                // 3) 更新规则统计 + 冷却时间
                if ($data['auto_topup_state']) {
                    Db::table('auto_topup_rules')->where('id', $data['rule_id'])->update($rules_data);
                }
                Db::commit();
            }
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            debugMessage('充值余额队列失败：' . $e->getMessage(), $e->getLine());
            if ($e->getCode() == card_not) {
                Db::table('auto_topup_rules')->where('id', $data['rule_id'])->update(['enabled' => state_zero, 'last_run_at' => $now]);
            }

            // 记录异常失败（不重复入账）
            Db::table('wallet_topup_logs')->insert([
                'uid'                    => $uid,
                'cards_id'               => $card_info->id ?? null,
                'amount_cents'           => $amount,
                'before_balance_cents'   => 0,
                'after_balance_cents'    => 0,
                'status'                 => state_two,
                'years' => $data['years'],
                'months' => $data['months'],
                'days' => $data['days'],
                'response_data'          => json_encode(['exception' => $e->getMessage()], JSON_UNESCAPED_UNICODE),
                'created_at'             => $now,
            ]);
            return false;
        }
    }
    /**
     * 处理消费失败
     *
     * @param \Throwable $e
     * @param $package
     */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        colorText($e->getLine(), 'red');
        colorText("充值余额队列失败：" . $e->getMessage() . '(' . getMillisecondTime() . ')', 'red');
        debugMessage("充值余额队列失败：" . $e->getMessage());
    }
}
