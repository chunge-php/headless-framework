<?php

namespace app\modules\user\wallet\fns;

use app\modules\user\wallet\model\AutoTopupRule;
use app\modules\user\wallet\model\Wallet;
use app\modules\user\wallet\model\WalletTopupLog;
use app\queue\redis\wallet\TopupBalance;
use support\Db;
use support\Redis;

class UserWalletFn
{
    private $wallet;
    private $autoTopupRule;
    private $walletTopupLog;
    public function __construct(Wallet $wallet, AutoTopupRule $autoTopupRule, WalletTopupLog $walletTopupLog)
    {
        $this->wallet = $wallet;
        $this->autoTopupRule = $autoTopupRule;
        $this->walletTopupLog = $walletTopupLog;
    }
    public function create($data)
    {
        $data['account_sid'] = md5($data['uid']);
        return $this->wallet->insertGetId(filterFields($data, $this->wallet));
    }
    public function getBalance($uid)
    {
        $user_balance =   Redis::hGetAll('b:' . $uid) ?? ['id' => $uid, 'balance' => 0];
        if (empty($user_balance) || (isset($user_balance['balance']) && $user_balance['balance'] <=0)) {
            $user_balance =  $this->wallet->select(['id', 'balance'])->where('uid', $uid)->first()?->toArray();
            if ($user_balance) {
                Redis::hMset('b:' . $uid, $user_balance);
            }else{
               $user_balance = ['id' => $uid, 'balance' => 0];
            }
        }
        $user_balance['balance'] = price_round2($user_balance['balance']);
        return $user_balance;
    }
    public function setAutoTopup($info)
    {
        $balance =   $this->getBalance($info['uid']);
        $auto_topup =  $this->autoTopupRule->where('uid', $info['uid'])->first();
        $info['wallets_id'] = $balance['id'];
        if ($auto_topup) {
            return  $this->autoTopupRule->where('id', $auto_topup['id'])->update(filterFields($info, $this->autoTopupRule));
        } else {
            return  $this->autoTopupRule->insertGetId(filterFields($info, $this->autoTopupRule));
        }
    }
    public function getAutoTopup($uid)
    {
        return  $this->autoTopupRule->where('uid', $uid)->first()?->toArray();
    }
    public function setReminderPrice($uid, $reminder_price)
    {
        return  $this->autoTopupRule->where('uid', $uid)->update(['reminder_price' => $reminder_price, 'reminder_status' => state_zero]);
    }
    public function recharge($info)
    {
        $TopupBalance =  new TopupBalance();
        $balance_info =  $this->getBalance($info['jwtUserId']);
        $card_id = Db::table('cards')->where('uid', $info['jwtUserId'])->where('status', state_one)->where('default_state', state_one)->value('id');
        if (!$card_id) tryFun('card_not_exists', '请先绑定银行卡');

        $data = [
            'uid' => $info['jwtUserId'],
            'wallet_id' => $balance_info['id'],
            'amount' => money_to_decimal($info['amount']),
            'now' => dayDateTime(),
            'today_count' => 0,
            'rule_id' => 0,
            'before_balance_cents' => money_to_decimal($balance_info['balance']),
            'years' => date('Y'),
            'months' => date('m'),
            'days' => date('d'),
            'auto_topup_state' => false,
        ];
        $is =   $TopupBalance->startFn($data);
        if (!$is) tryFun('recharge_failed', info_err);
        return $is;
    }
    public function autoTopupIndex($info, $limit = 20, $offset = 1): array
    {
        // 构建基础查询（复用查询条件）
        $baseQuery = $this->walletTopupLog
            ->join('cards', 'wallet_topup_logs.cards_id', 'cards.id')
            ->when(!empty($info['years']), fn($query) => $query->where('wallet_topup_logs.years', $info['years']))
            ->when(!empty($info['months']), fn($query) => $query->where('wallet_topup_logs.months', $info['months']))
            ->when(isset($info['status']) && $info['status'] > -1, fn($query) => $query->where('wallet_topup_logs.status', $info['status']))
            ->when(!empty($info['code']), fn($query) => $query->where('wallet_topup_logs.code', $info['code']))
            ->when(!empty($info['begin_time']) && !empty($info['end_time']), fn($query) => $query->whereBetween('wallet_topup_logs.created_at', [$info['begin_time'], $info['end_time']]))
            ->where('wallet_topup_logs.uid', $info['jwtUserId']);

        // 获取总金额
        $amount_cents = (clone $baseQuery)->sum('wallet_topup_logs.amount_cents');

        // 获取列表查询
        $listQuery = (clone $baseQuery)->select([
            'wallet_topup_logs.id',
            'wallet_topup_logs.code',
            'cards.later_number',
            'cards.accttype',
            'wallet_topup_logs.amount_cents',
            'wallet_topup_logs.before_balance_cents',
            'wallet_topup_logs.after_balance_cents',
            'wallet_topup_logs.status',
            'wallet_topup_logs.years',
            'wallet_topup_logs.months',
            'wallet_topup_logs.days',
            'wallet_topup_logs.created_at',
            'wallet_topup_logs.updated_at'
        ]);

        // 获取总数
        $total = $listQuery->count();

        if ($total < 1) {
            return [
                'total' => 0,
                'amount_cents' => $amount_cents,
                'list' => []
            ];
        }

        // 获取列表数据
        $list = $listQuery
            ->orderBy('wallet_topup_logs.id', 'desc')
            ->when(isset($info['page_state']) && $info['page_state'] == state_one, function ($query) use ($limit, $offset) {
                return $query->limit($limit)
                    ->offset($offset); // 修正offset计算
            })
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'amount_cents' => $amount_cents,
            'list' => $list
        ];
    }
    public function autoTopupStatus($uid, $state)
    {
        return  $this->autoTopupRule->where('uid', $uid)->update(['enabled' => $state]);
    }
}
