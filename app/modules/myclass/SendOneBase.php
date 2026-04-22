<?php

namespace app\modules\myclass;


use app\modules\myclass\MailgunService;
use app\modules\myclass\TwilioSms;
use app\modules\sms\body\model\MessageLog;
use app\modules\sms\body\model\SmsBatchLot;
use support\Db;
use support\Redis;
use Webman\RedisQueue\Redis as RedisQueueRedis;

class SendOneBase
{
    private $balance_key;
    private $total_balance = 0;
    private $error_total = 0;
    private $content_price;
    private $twilioSms;
    private $MailgunService;
    private $total_money;
    private $consume_number;
    private $success_total;
    private $data;
    private $balance_id;
    public function start($res)
    {
        $this->total_balance  = 0;
        $this->total_money = 0;
        $this->consume_number = 0;
        $this->success_total = 0;
        $this->balance_id = 0;
        $this->data = [];
        try {
            if ($res) {
                $uid = $res['uid'];
                $batch_lot_id = $res['batch_lot_id'];
                $scope_id = $res['scope_id'];
                $batchs_id = $res['batchs_id'];
                $log_id = $res['log_id'] ? explode(',', $res['log_id']) : [];
                $this->balance_key = 'b:' . $uid;
                $user_balance =   Redis::hGetAll($this->balance_key) ?? feature('user.wallet.getBalance', $uid);
                $raw_balance = $user_balance['balance'] ?? 0;
                $this->balance_id = $user_balance['id'] ?? 0;
                $this->total_balance = $raw_balance;
                if ($raw_balance <= 0) {
                    feature('batchLog.update', ['id' => $batchs_id, 'status' => state_three, 'describe' => trans('balance_insufficient')]);
                    return;
                }
                feature('batchLog.update', ['id' => $batchs_id, 'status' => state_two]);
                SmsBatchLot::query()->where('id', $scope_id)
                    ->increment('executed_count', 1, ['status' => state_two]);
                $SmsBatchLots = SmsBatchLot::query()->where('id', $scope_id)->first()?->toArray();
                if (!empty($SmsBatchLots)) {
                    $this->twilioSms = new TwilioSms();
                    $this->MailgunService =  new MailgunService();
                    $this->content_price =  getContentPriceLength($SmsBatchLots['send_type'], $SmsBatchLots['content'], $SmsBatchLots['price']);
                    $this->content_price['total_money'] = price_round2($this->content_price['total_money']);
                    $this->data = $SmsBatchLots;
                    $query  =   MessageLog::query()
                        ->select(['id', 'account_number', 'type'])
                        ->where('uid', $uid)
                        ->where('sms_batch_lots_id', $batch_lot_id)
                        ->when(!empty($log_id) && $log_id != ['all'], function ($query) use ($log_id) {
                            return $query->whereIn('id', $log_id);
                        });
                    $this->total_money = 0;
                    // $total = $query->count();
                    $query->chunkById(1000, function ($rows) use ($SmsBatchLots, $uid, $scope_id, $batchs_id) {
                        if ($rows) {
                            $user_balance =   Redis::hGetAll($this->balance_key) ?? feature('user.wallet.getBalance', $uid);
                            $raw_balance = $user_balance['balance'] ?? 0;
                            $this->total_balance = $raw_balance;
                            foreach ($rows as $row) {
                                $log_data = [
                                    'uid' => $uid,
                                    'sms_batch_lots_id' => $scope_id,
                                    'account_number' => $row['account_number'],
                                    'type' => $SmsBatchLots['send_type'],
                                    'total_money' => $this->content_price['total_money'],
                                    'consume_number' => $this->content_price['consume_number'],
                                    'status' => state_two
                                ];
                                $message_log_id =  MessageLog::query()->insertGetId($log_data);
                                $response = $this->phoneSend($row['account_number']);
                                if (!$response['success']) {
                                    MessageLog::query()->where('id', $message_log_id)->update([
                                        'unusual_msg' => $response['error'],
                                        'status' => state_three,
                                    ]);
                                } else {
                                    MessageLog::query()->where('id', $message_log_id)->update([
                                        'status' => state_one,
                                    ]);
                                }
                            }
                            feature('batchLog.update', ['id' => $batchs_id, 'status' => state_one, 'total' => $this->success_total + $this->error_total, 'success' => $this->success_total, 'fail' => $this->error_total]);
                            $status  = $this->success_total == 0 && $this->error_total > 0 ? state_three : state_one;
                            if ($this->success_total > 0 && $this->error_total > 0) {
                                $status = state_four;
                            }
                            SmsBatchLot::query()->where('id', $scope_id)->update([
                                'status' => $status,
                                'total_money' => price_round2($this->total_money),
                                'consume_number' => $this->consume_number,
                                'success_total' => $this->success_total,
                                'error_total' => $this->error_total,
                            ]);
                            $money = price_round2($this->total_money);
                            Redis::hIncrByFloat($this->balance_key, 'balance', -$money);
                        }
                    }, 'id', 'id');
                } else {
                    feature('batchLog.update', ['id' => $batchs_id, 'status' => state_three, 'describe' => trans('batch_lot_not_exists')]);
                    SmsBatchLot::query()->where('id', $scope_id)
                        ->update(['status' => state_three, 'unusual_msg' => trans('batch_lot_not_exists')]);
                    return;
                }
            }
        } catch (\Exception $e) {
            feature('batchLog.update', ['id' => $batchs_id, 'status' => state_three, 'describe' => $e->getMessage()]);
            SmsBatchLot::query()->where('id', $scope_id)
                ->update(['status' => state_three, 'unusual_msg' => $e->getMessage()]);
            return;
        }
    }
    /**
     * 账号直接发送短信
     * @param mixed $tlp
     * @param mixed $content
     * @return array
     */
    public function phoneSend($tlp)
    {

        // $balance = Redis::hGet($this->balance_key, 'balance');
        $balance =  $this->total_balance - $this->content_price['total_money'];
        if ($balance < 0) {
            $this->error_total++;
            return ['success' => false, 'error' => trans('balance_insufficient')];
        }
        if ($this->data['send_type'] == state_zero) {
            $response =  $this->sendSms($tlp, $this->data['content']);
        } elseif ($this->data['send_type'] == state_one) {
            $response =  $this->sendMms($tlp,  $this->data['content']);
        } elseif ($this->data['send_type'] == state_two) {
            //发送邮件
            $response  = $this->MailgunService->sendEmail($tlp, $this->data['subject'], $this->data['content']);
        } else {
            $response =  ['success' => false, 'error' => '未知错误'];
        }
        if ($response['success']) {
            $this->total_balance -= $this->content_price['total_money'];
            //成功
            $this->total_money += $this->content_price['total_money'];
            $this->consume_number += $this->content_price['consume_number'];
            $this->success_total++;
            RedisQueueRedis::send('wallet-update', [
                'bill_type' => state_zero,
                'sms_batch_lots_id' => $this->data['id'],
                'total_money' => $this->content_price['total_money'],
                'send_type' => $this->data['send_type'],
                'years' => $this->data['years'],
                'months' => $this->data['months'],
                'days' => $this->data['days'],
                'uid' => $this->data['uid'],
                'id' => $this->balance_id,
                'new_balance' => $this->total_balance
            ]);
            return $response;
        } else {
            //失败
            $this->error_total++;
            return $response;
        }
    }
    /**
     * 模拟短信发送
     * @param mixed $arr
     * @return array
     */
    private  function sendSms($send_to, $content)
    {
        // 此处可补充真实短信发送逻辑
        return  $this->twilioSms->sendSms($send_to, $content);
    }
    /**
     * 模拟彩信发送
     * @param mixed $arr
     * @return array
     */
    private  function sendMms($send_to, $content)
    {
        // 此处可补充真实短信发送逻辑
        return  $this->twilioSms->sendMms($send_to, $content, explode(',', $this->data['mms_url']));
    }
}
