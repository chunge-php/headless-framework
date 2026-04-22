<?php

namespace app\modules\myclass;


use app\modules\groupUser\model\GroupUser;
use app\modules\myclass\CsvChunker;
use app\modules\myclass\MailgunService;
use app\modules\myclass\RedisLock;
use app\modules\myclass\TwilioSms;
use app\modules\sms\body\model\MessageLog;
use app\modules\sms\body\model\SmsBatchLot;
use app\modules\user\addressbook\model\AddressBook;
use app\modules\user\wallet\model\AutoTopupRule;
use app\queue\redis\wallet\TopupBalance;
use Illuminate\Database\Query\Expression;
use support\Db;
use support\Redis;
use Webman\RedisQueue\Redis as RedisQueueRedis;

class SendBase
{
    public $SmsBatchLot;
    public $twilioSms;
    public $content_price;
    public $MessageLog;
    public $MailgunService;
    public $data;
    protected $total_money = 0;
    protected $consume_number = 0;
    protected $success_total = 0;
    protected $error_total = 0;
    protected $balance_key;
    protected $TopupBalance;
    protected $AutoTopupRule;
    protected $auto_topup_rules;
    protected $now;
    private $total_balance;
    private $balance_id;
    private $channel_type;
    public function start($data)
    {
        $this->total_money = 0;
        $this->consume_number = 0;
        $this->success_total = 0;
        $this->error_total = 0;
        $uid = $data['uid'];
        $this->channel_type = isset($data['channel_type']) ? $data['channel_type'] : 0;
        $this->balance_key = 'b:' . $uid;
        $this->data = $data;
        $this->SmsBatchLot =  new SmsBatchLot();
        $this->twilioSms = new TwilioSms();
        $this->MessageLog =   new MessageLog();
        $this->MailgunService =  new MailgunService();
        $this->TopupBalance =    new TopupBalance();
        $this->AutoTopupRule =   new AutoTopupRule();
        $this->now = dayDateTime();
        $lockKey = "lock:wallet:$uid";
        $token = uniqid('lock_', true);
        // 尝试加锁，最多重试 5 次（每次等待 200ms）


        try {
            // $maxRetry = 10;
            // while ($maxRetry-- > 0) {
            //     if (RedisLock::acquire($lockKey, $token, 10)) {
            //         break;
            //     }
            //     usleep(200000); // 0.2 秒
            // }
            // if ($maxRetry <= 0) {
            //     debugMessage("UID {$uid} 获取钱包锁失败，跳过任务");
            //     return ['success' => false, 'error' => '钱包锁定中，稍后重试'];
            // }
            $user_balance =   Redis::hGetAll($this->balance_key) ?? feature('user.wallet.getBalance', $uid);
            $raw_balance = $user_balance['balance'] ?? 0;
            $this->total_balance = $raw_balance;
            $this->balance_id = $user_balance['id'];
            feature('batchLog.update', ['id' => $data['batchs_id'], 'status' => state_two]);
            $this->auto_topup_rules =   $this->AutoTopupRule->select(['id', 'wallets_id', 'today_count', 'threshold_cents', 'topup_amount_cents', 'reminder_status', 'reminder_price', 'enabled'])->where('uid', $this->data['uid'])->first()?->toArray();
            $this->content_price =  getContentPriceLength($data['send_type'], $data['content'], $data['price']);
            $this->content_price['total_money'] = price_round2($this->content_price['total_money']);
            $this->SmsBatchLot->where('id', $data['sms_batch_lots_id'])
                ->increment('executed_count', 1, ['status' => state_two]);
            if ($this->data['account_number'] == all_str) {
                $this->allEachUser();
            } else {
                $this->forEachPhone($this->data['account_number']);
                $this->tagsForEach();
                $this->groupForEach();
            }
            $total = $this->success_total + $this->error_total;
            if ($this->data['account_number'] == all_str && $total <= 0) {
                feature('batchLog.delete', ['id' => $data['batchs_id'], 'uid' => $data['uid']]);
            } else {
                feature('batchLog.update', ['id' => $data['batchs_id'],  'total' => $total, 'success' => $this->success_total, 'fail' => $this->error_total]);
            }

            $status  = $this->success_total == 0 && $this->error_total > 0 ? state_three : state_one;
            if ($this->success_total > 0 && $this->error_total > 0) {
                $status = state_four;
            }
            if ($this->data['account_number'] == all_str && $total <= 0) {
                feature('sms.body.delete', ['id' => $data['sms_batch_lots_id'], 'uid' => $data['uid']]);
            } else {
                feature('batchLog.update', ['id' => $data['batchs_id'], 'status' => state_one, 'total' => $this->success_total + $this->error_total, 'success' => $this->success_total, 'fail' => $this->error_total]);
                $this->SmsBatchLot->where('id', $data['sms_batch_lots_id'])->update([
                    'status' => $status,
                    'total_money' => price_round2($this->total_money),
                    'consume_number' => $this->consume_number,
                    'success_total' => $this->success_total,
                    'error_total' => $this->error_total,
                ]);
            }
            $money = price_round2($this->total_money);
            Redis::hIncrByFloat($this->balance_key, 'balance', -$money);
        } catch (\Exception $e) {
            debugMessage([$e->getLine(), $e->getMessage()], '发送短信失败1');
            feature('batchLog.update', ['id' => $data['batchs_id'], 'status' => state_three, 'total' => $this->success_total + $this->error_total, 'success' => $this->success_total, 'fail' => $this->error_total]);
            $this->SmsBatchLot->where('id', $data['sms_batch_lots_id'])->update([
                'status' => state_three,
                'total_money' => $this->total_money,
                'consume_number' => $this->consume_number,
                'success_total' => $this->success_total,
                'error_total' => $this->error_total,

            ]);
        }
    }
    public function allEachUser()
    {
        $model = new AddressBook();
        $phone_arr =   $model->whereNotNull('phone')->where('month_day', substr(dayDate(), 5))->where('uid', $this->data['uid'])->pluck('phone')->toArray();
        if (!empty($phone_arr)) {
            $this->forEachPhone($phone_arr);
        }
    }
    public function groupForEach()
    {
        if (!empty($this->data['group_id'])) {
            $model = new GroupUser();
            // 取出所有 file_url
            $fileUrls = $model->whereIn('id', $this->data['group_id'])
                ->whereNotNull('file_url')
                ->pluck('file_url')
                ->toArray();
            foreach ($fileUrls as $url) {
                // 判断路径是否为相对路径
                $path = str_starts_with($url, '/')
                    ? public_path($url)
                    : $url;
                if (!is_file($path)) {
                    // 文件不存在跳过
                    continue;
                }

                try {
                    $reader = (new CsvChunker($path))
                        ->withHeader(true)
                        ->encoding('auto');
                    foreach ($reader->chunks(1000) as $chunk) {
                        $phones = [];
                        foreach ($chunk as $row) {
                            // 更安全地读取列
                            $phone = array_values($row)[2]
                                ?? null;
                            $phone = phone_to_digits($phone);
                            if ($phone) {
                                $is_phone = isUsMobile($phone);
                                if ($is_phone) {
                                    $phones[] = $phone;
                                }
                            }
                        }
                        // 避免空调用
                        if (!empty($phones)) {
                            $this->forEachPhone($phones);
                        }
                    }
                } catch (\Throwable $e) {
                    // 某一个文件坏了，不影响其他文件
                    debugMessage([
                        'csv_error' => $e->getMessage(),
                        'file' => $path
                    ], '用户分组读取异常');
                }
            }
        }

        // if (!empty($this->data['group_id'])) {
        //     $model =  new GroupUser();
        //     $file_url_arr =   $model->whereIn('id', $this->data['group_id'])->whereNotNull('file_url')->pluck('file_url')->toArray();
        //     if (!empty($file_url_arr)) {
        //         foreach ($file_url_arr as $val) {
        //             $reader = (new CsvChunker(public_path($val)))
        //                 ->withHeader(true)        // 首行为表头
        //                 ->encoding('auto');       // 自动识别并转为 UTF-8
        //             foreach ($reader->chunks(500) as $chunk) {
        //                 $phone_arr = [];
        //                 foreach ($chunk as $row) {
        //                     $phone = phone_to_digits(array_values($row)[2] ?? '');
        //                     if (!empty($phone) && $phone != null && $phone != 'null') {
        //                         $phone_arr[] = $phone;
        //                     }
        //                 }
        //                 $this->forEachPhone($phone_arr);
        //             }
        //         }
        //     }
        // }
    }
    /**
     * 根据标签批量发送短信
     * @return void
     */
    public function tagsForEach()
    {
        $model = new AddressBook();
        $phone_arr =   $model->whereIn('id', function ($query) {
            return $query->from('tag_books')->select('target_id')->whereIn('tags_id', $this->data['tags_id'])->where('uid', $this->data['uid'])->where('target_type', addressbook_str)->pluck('target_id')->toArray();
        })->whereNotNull('phone')->pluck('phone')->toArray();
        if (!empty($phone_arr)) {
            $this->forEachPhone($phone_arr);
        }
    }
    /**
     * 手机号循环发送短信
     */
    public function forEachPhone($account_number)
    {
        try {
            foreach ($account_number as $val) {
                // 统一成字符串并修剪两端空白
                $val = trim((string)$val);
                // 过滤各种“空”的情况：null/空串/'null'/'NULL'/仅空白
                if ($val === '' || strtolower($val) === 'null') {
                    continue;
                }
                //如果设置了自动充值立马执行
                if (!empty($this->auto_topup_rules)) {

                    // $new_balance = Redis::hGet($this->balance_key, 'balance');

                    if ($this->auto_topup_rules['enabled'] == state_one && $this->auto_topup_rules['threshold_cents'] >= $this->total_balance) {
                        $info = [
                            'uid' => $this->data['uid'],
                            'wallet_id' => $this->auto_topup_rules['wallets_id'],
                            'amount' => $this->auto_topup_rules['topup_amount_cents'],
                            'now' => $this->now,
                            'today_count' => $this->auto_topup_rules['today_count'],
                            'rule_id' => $this->auto_topup_rules['id'],
                            'before_balance_cents' => money_to_decimal($this->total_balance),
                            'years' => $this->data['years'],
                            'months' => $this->data['months'],
                            'days' => $this->data['days'],
                        ];
                        $this->TopupBalance->startFn($info);
                        $user_balance =   Redis::hGetAll($this->balance_key) ?? feature('user.wallet.getBalance', $this->data['uid']);
                        $raw_balance = $user_balance['balance'] ?? 0;
                        $this->total_balance = $raw_balance - $this->total_money;
                        $this->auto_topup_rules =   $this->AutoTopupRule->select(['id', 'wallets_id', 'today_count', 'threshold_cents', 'topup_amount_cents', 'enabled'])->where('uid', $this->data['uid'])->first()?->toArray();
                    }
                }
                $log_data = [
                    'uid' => $this->data['uid'],
                    'sms_batch_lots_id' => $this->data['sms_batch_lots_id'],
                    'account_number' => $val,
                    'type' => $this->data['send_type'],
                    'total_money' => $this->content_price['total_money'],
                    'consume_number' => $this->content_price['consume_number'],
                    'status' => state_two
                ];
                $message_log_id =  $this->MessageLog->insertGetId($log_data);
                $response = $this->phoneSend($val);
                if (!$response['success']) {
                    $this->MessageLog->where('id', $message_log_id)->update([
                        'unusual_msg' => $response['error'],
                        'status' => state_three,
                    ]);
                } else {
                    $this->MessageLog->where('id', $message_log_id)->update([
                        'status' => state_one,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->error_total++;
            if (!empty($message_log_id)) {
                $this->MessageLog->where('id', $message_log_id)->update([
                    'unusual_msg' => $e->getMessage(),
                    'status' => state_three,
                ]);
            }
            debugMessage($e->getMessage(), '手机号发送失败');
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
            $response =  ['success' => false, 'error' => trans('info_err')];
        }
        if ($response['success']) {
            $this->total_balance -= $this->content_price['total_money'];
            //成功
            $this->total_money += $this->content_price['total_money'];
            $this->consume_number += $this->content_price['consume_number'];
            $this->success_total++;


            // Redis::hIncrByFloat($this->balance_key, 'balance', -$this->content_price['total_money']);
            // $new_balance = Redis::hGet($this->balance_key, 'balance');
            RedisQueueRedis::send('wallet-update', [
                'bill_type' => state_zero,
                'sms_batch_lots_id' => $this->data['sms_batch_lots_id'],
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
        $from = [config('app.TWILIO_NUMBER'), config('app.TWILIO_NUMBER2')];
        $from = $from[$this->channel_type] ?? config('app.TWILIO_NUMBER');
        return  $this->twilioSms->sendSms($send_to, $content, $from);
    }
    /**
     * 模拟彩信发送
     * @param mixed $arr
     * @return array
     */
    private  function sendMms($send_to, $content)
    {
        // 此处可补充真实短信发送逻辑
        $from = [config('app.TWILIO_NUMBER'), config('app.TWILIO_NUMBER2')];
        $from = $from[$this->channel_type] ?? config('app.TWILIO_NUMBER');
        return  $this->twilioSms->sendMms($send_to, $content, explode(',', $this->data['mms_url']), ['from' => $from]);
    }
}
