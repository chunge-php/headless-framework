<?php

namespace app\queue\redis\bill;

use app\modules\user\wallet\model\Bill;
use app\modules\user\wallet\model\Wallet;
use Webman\RedisQueue\Consumer;
use Illuminate\Database\Query\Expression;
use support\Db;

class Balance implements Consumer
{
    public $queue = 'wallet-update';
    public $connection = 'default'; // 连接名，对应 config/redis_queue.php 文件中的连接
    public function consume($data)
    {
        // debugMessage($data, '扣款余额');
        // ['bill_type'=>$bill_type,'sms_batch_lots_id,=>$sms_batch_lots_id,'total_money' => $this->content_price['total_money'],$this->data['send_type'], 'uid' => $this->data['uid'], 'id' => $balance_id, 'new_balance' => $new_balance];
        Db::beginTransaction();
        try {
            $WalletModel  =  new Wallet();
            $billModel =  new Bill();
            $is =  $WalletModel->where('id', $data['id'])->where('uid', $data['uid'])->update([
                'balance' => new Expression('COALESCE(`balance`, 0) - ' . $data['total_money']),
            ]);
            if ($is) {
                //记录账单
                // 生成并发安全的账单编号（如：BILL-20251021-000123）
                $biz =    $data['bill_type'] == state_one ? 'T' : 'C';
                $code = feature('user.wallet.nextBillNo', $biz, 6, 'America/New_York');

                $data = [
                    'uid' => $data['uid'],
                    'batch_id' => $data['sms_batch_lots_id'],
                    'total_money' => $data['total_money'],
                    'send_type' => $data['send_type'],
                    'code' => $code,
                    'years' => $data['years'],
                    'months' => $data['months'],
                    'days' => $data['days'],
                    'balance' => $data['new_balance'],
                    'bill_type' => $data['bill_type'],
                ];
                $billModel->insert($data);
                Db::commit();
            } else {
                Db::rollBack();
                debugMessage('更新失败', '扣款余额');
            }
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), '扣款余额异常');
            Db::rollBack();
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
        colorText("导入联系人队列失败：" . $e->getMessage() . '(' . getMillisecondTime() . ')', 'red');
        debugMessage("导入联系人队列失败：" . $e->getMessage());
    }
}
