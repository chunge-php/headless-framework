<?php

namespace app\modules\sms\body\fns;

use app\modules\sms\body\model\LesseeLog;
use app\modules\sms\body\model\MessageLog;
use app\modules\sms\body\model\SmsBatchLot;
use app\modules\sms\body\model\SmsBatchSourc;
use app\modules\sms\body\model\SmsBill;
use app\modules\sms\body\model\SmsChannelLog;
use app\modules\sms\config\model\SmsPrice;
use support\Db;
use Webman\RedisQueue\Redis as RedisQueueRedis;

class  BodyFn
{
    protected $model;
    protected $smsPrice;
    protected $smsBatchSourc;
    protected $messageLog;
    protected $smsBill;
    protected $lesseeLog;
    protected $smsChannelLog;
    public function __construct(SmsBatchLot $model, SmsPrice $smsPrice, SmsBatchSourc $smsBatchSourc, MessageLog $messageLog, SmsBill $smsBill, LesseeLog    $lesseeLog, SmsChannelLog $smsChannelLog)
    {
        $this->model = $model;
        $this->smsPrice = $smsPrice;
        $this->smsBatchSourc = $smsBatchSourc;
        $this->messageLog = $messageLog;
        $this->smsBill = $smsBill;
        $this->lesseeLog = $lesseeLog;
        $this->smsChannelLog = $smsChannelLog;
    }
    public function index($info, $limit = 20, $offset = 1): array
    {
        $where = $this->model
            ->select(['sms_batch_lots.*', 'lessee_logs.name as lessee_name', 'lessee_logs.code as lessee_code'])
            ->leftJoin('lessee_logs', 'lessee_logs.sms_batch_lots_id', 'sms_batch_lots.id')
            ->where('sms_batch_lots.uid', $info['jwtUserId'])
            ->when(isset($info['scope_id']) && $info['scope_id'] > -1, fn($query) => $query->where('sms_batch_lots.scope_id', $info['scope_id']))
            ->when(isset($info['send_type']) && $info['send_type'] > -1, fn($query) => $query->where('sms_batch_lots.send_type', $info['send_type']))
            ->when(isset($info['status']) && $info['status'] > -1, fn($query) => $query->where('sms_batch_lots.status', $info['status']))
            ->when(isset($info['subscribe_type']) && $info['subscribe_type'] > -1, fn($query) => $query->where('sms_batch_lots.subscribe_type', $info['subscribe_type']))
            ->when(!empty($info['seek']), function ($query) use ($info) {
                $query->where(function ($query) use ($info) {
                    $query->where('lessee_logs.name', 'like', '%' . $info['seek'] . '%')
                        ->orWhere('lessee_logs.code', 'like', '%' . $info['seek'] . '%');
                });
            });
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('sms_batch_lots.updated_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            return ['total' => $total, 'list' => $list];
        }
    }
    public function monthIndex($info, $limit = 20, $offset = 1)
    {
        $where = $this->smsBill->select(['sms_bills.*', 'wallets.account_sid'])->join('wallets', 'wallets.uid', 'sms_bills.uid')->where('sms_bills.uid', $info['jwtUserId']);
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('sms_bills.id', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();
            foreach ($list as &$item) {
                $item['send_total'] = $item['total_sms'] + $item['total_mms'] + $item['total_email'];
            }
            return ['total' => $total, 'list' => $list];
        }
    }
    public function anewSend($info)
    {
        feature('user.wallet.getBalance', $info['uid']);

        try {
            $batch_lot_id = $info['batch_lot_id'];
            $batch_lot_show =  $this->model->select(['uid', 'price_id', 'subscribe_type', 'mms_url', 'subject', 'content'])->where('id', $batch_lot_id)->where('uid', $info['uid'])->first()?->toArray();
            if (empty($batch_lot_show)) tryFun('batch_lot_not_exists', info_err);
            $code =  $this->model->where('uid', $info['uid'])->orderBy('code', 'desc')->value('code') ?? 10000000;
            $price_info = $this->smsPrice->where('id', $batch_lot_show['price_id'])->first()?->toArray();
            if (empty($price_info)) tryFun('price_not_exists', info_err);
            $info = $batch_lot_show;
            $info['price'] = $price_info['price'];
            $info['code'] = $code + 1;
            $info['send_type'] = $price_info['type'];
            $info['years']  = date('Y');
            $info['months']  = date('m');
            $info['days']  = date('d');
            $sms_batch_lots_id =  $this->model->insertGetId(filterFields($info, $this->model));
            $batch_data = [
                'name' => 'Message resent:' . dayDateTime(),
                'uid' => (int)$info['uid'],
                'batch_lot_id' => $batch_lot_id,
                'scope_id' => $sms_batch_lots_id,
                'scope' => sms_batch_lots,
                'log_id' => $info['log_id'] ?? []
            ];
            $batchs_id =  feature('batchLog.create', $batch_data);
            $batch_data['batchs_id'] = $batchs_id;
            RedisQueueRedis::send('sms-send-one', $batch_data);
            return $batchs_id;
        } catch (\Exception $e) {
            debugMessage($e->getMessage(), '重新发送异常');
            return 0;
        }
    }
    /**
     * Summary of create
     * @param mixed $info
     */
    public function create($info)
    {
        $balance_info =  feature('user.wallet.getBalance', $info['jwtUserId']);
        if ($balance_info['balance'] <= 0) tryFun('balance_insufficient', info_err);
        if (empty($info['account_number']) && empty($info['tags_id']) && empty($info['group_id'])) tryFun('phone_arr_empty', info_err);
        $info['uid'] = $info['jwtUserId'];
        $code =  $this->model->where('uid', $info['uid'])->orderBy('code', 'desc')->value('code') ?? 10000000;
        $info['code'] = $code + 1;
        $price_info = $this->smsPrice->where('id', $info['price_id'])->first()->toArray();
        if (empty($price_info)) tryFun('price_not_exists', info_err);
        Db::beginTransaction();
        try {
            $info['price'] = $price_info['price'];
            $info['send_type'] = $price_info['type'];
            $info['years']  = date('Y');
            $info['months']  = date('m');
            $info['days']  = date('d');
            $sms_batch_lots_id =  $this->model->insertGetId(filterFields($info, $this->model));
            if (!empty($info['tags_id'])) {
                $data_tags = [];
                foreach ($info['tags_id'] as $tag_id) {
                    $data_tags[] = [
                        'sms_batch_lots_id' => $sms_batch_lots_id,
                        'source_id' => $tag_id,
                        'source_type' => state_one
                    ];
                }
                $this->smsBatchSourc->insert($data_tags);
            }
            if (!empty($info['group_id'])) {
                $data_group = [];
                foreach ($info['group_id'] as $group_id) {
                    $data_group[] = [
                        'sms_batch_lots_id' => $sms_batch_lots_id,
                        'source_id' => $group_id,
                        'source_type' => state_two
                    ];
                }
                $this->smsBatchSourc->insert($data_group);
            }
            if (!empty($info['account_number']) && $info['account_number'] != all_str) {
                $data_phone = [];
                $sms_channel_data = [];
                $sms_admin_channel_data = [];
                if (is_string($info['account_number'])) {
                    $info['account_number'] = explode(',', $info['account_number']);
                }
                $row_phone = [];
                if (isset($info['channel_type']) && ($info['send_type'] == state_zero || $info['send_type'] == state_one)) {
                    $res_raw =  $this->smsChannelLog->select(['to', 'channel_type', 'uid'])->whereIn('to', $info['account_number'])->where('channel_type', $info['channel_type'])->get()->toArray();
                    foreach ($res_raw as $val_phone) {
                        if ($val_phone['uid'] != config('app.sms_quick_uid')) {
                            $row_phone[$val_phone['to'] . '|' . $val_phone['channel_type'] . '|' . $val_phone['uid']] = $val_phone['channel_type'];
                        }
                        $row_phone[$val_phone['to'] . '|' . $val_phone['channel_type'] . '|' . config('app.sms_quick_uid')] = $val_phone['channel_type'];
                    }
                }
                foreach ($info['account_number'] as $phone) {
                    $data_phone[] = [
                        'sms_batch_lots_id' => $sms_batch_lots_id,
                        'account_number' => $phone,
                        'source_type' => state_zero
                    ];
                    if (isset($info['channel_type'])) {
                        if ($info['send_type'] == state_zero || $info['send_type'] == state_one) {
                            if (!isset($row_phone[$phone . '|' . (int)$info['channel_type'] . '|' . $info['uid']])) {
                                $sms_channel_data[] = [
                                    'to' => $phone,
                                    'channel_type' => (int)$info['channel_type'],
                                    'uid' => $info['uid'],
                                ];
                                if ($info['channel_type'] == state_three) {
                                    $is_phone =  phone_to_digits($phone);
                                    if ($is_phone) {
                                        RedisQueueRedis::send('game-task', ['uid' => $info['uid'], 'to' => $phone, 'channel_type' => state_three]);
                                    }
                                }
                                $row_phone[$phone . '|' . (int)$info['channel_type'] . '|' . $info['uid']] = $info['channel_type'];
                            }
                            if (!isset($row_phone[$phone . '|' . (int)$info['channel_type'] . '|' . config('app.sms_quick_uid')])) {
                                $sms_admin_channel_data[] = [
                                    'to' => $phone,
                                    'channel_type' => (int)$info['channel_type'],
                                    'uid' =>  config('app.sms_quick_uid'),
                                ];
                                if ($info['channel_type'] == state_three) {
                                    $is_phone =  phone_to_digits($phone);
                                    if ($is_phone) {
                                        RedisQueueRedis::send('game-task', ['uid' =>  config('app.sms_quick_uid'), 'to' => $phone, 'channel_type' => state_three]);
                                    }
                                }
                                $row_phone[$phone . '|' . (int)$info['channel_type'] . '|' . config('app.sms_quick_uid')] = $info['channel_type'];
                            }
                        }
                    }
                }
                $this->smsBatchSourc->insert($data_phone);
                if (!empty($sms_channel_data)) {
                    $this->smsChannelLog->insert($sms_channel_data);
                }
                if (!empty($sms_admin_channel_data)) {
                    $this->smsChannelLog->insert($sms_admin_channel_data);
                }
            }
            if (!empty($info['lessee_code'])) {
                $this->lesseeLog->insert(
                    ['name' => $info['lessee_name'] ?? '', 'sms_batch_lots_id' => $sms_batch_lots_id, 'code' => $info['lessee_code']]
                );
            }

            $info['sms_batch_lots_id'] = $sms_batch_lots_id;
            feature('user.wallet.getBalance', $info['uid']);
            $names = $info['name'] ?? '';
            if ($info['send_type'] == state_zero) {
                $name = 'SMS';
            } elseif ($info['send_type'] == state_one) {
                $name = 'MMS';
            } elseif ($info['send_type'] == state_two) {
                $name = 'EMAIL';
            } else {
                $name = 'UNKNOWN';
            }
            $scope =   sms_batch_lots;
            if (isset($info['scope_id']) && $info['scope_id'] > 0) {
                $sms_batch_lots_id = $info['scope_id'];
                $scope = $info['scope'] ?? '';
            }
            $batch_data = ['name' => $names . $name . ':' . dayDateTime(), 'uid' => (int)$info['uid'], 'scope_id' => $sms_batch_lots_id, 'scope' => $scope, 'created_at' => dayDateTime()];
            $batchs_id = 0;
            $batchs_id =  feature('batchLog.create', $batch_data);
            $info['batchs_id'] = $batchs_id;
            Db::commit();
            if ($info['send_type'] == state_zero || $info['send_type'] == state_one) {
                RedisQueueRedis::send('sms-send-sms', $info);
            } elseif ($info['send_type'] == state_two) {
                RedisQueueRedis::send('email-send-email', $info);
            }
            return $batchs_id;
        } catch (\Exception $e) {
            Db::rollBack();
            debugMessage($e->getMessage(), '创建异常');
            return 0;
        }
    }
    public function update($id, $info)
    {
        $is  = $this->model->where('uid', $info['jwtUserId'])->where('id', '<>', $id)->where('name', $info['name'])->exists();
        if ($is) tryFun('tag_name_exists', info_err);
        return $this->model->where('id', $id)->update(filterFields($info, $this->model));
    }
    public function showIndex($info, $limit = 20, $offset = 1): array
    {
        $show =  $this->model->where('id', $info['id'])->where('uid', $info['jwtUserId'])->first()?->toArray();
        if ($show) {
            $where = $this->messageLog
                ->where('uid', $info['jwtUserId'])
                ->where('sms_batch_lots_id', $show['id'])
                ->when(!empty($info['seek']), fn($query) => $query->where('account_number', 'like', '%' . $info['seek'] . '%'))
                ->when(isset($info['type']) && $info['type'] > -1, fn($query) => $query->where('type', $info['type']))
                ->when(isset($info['status']) && $info['status'] > -1, fn($query) => $query->where('status', $info['status']));
            $total = $where->count();
            if ($total < 1) {
                return ['info' => null, 'total' => 0, 'list' => []];
            } else {
                $list  = $where
                    ->orderBy('updated_at', 'desc')
                    ->limit($limit)
                    ->offset($offset)
                    ->get()
                    ->toArray();
                return ['info' => $show, 'total' => $total, 'list' => $list];
            }
        }
        return ['info' => null, 'total' => 0, 'list' => []];
    }
    public function delete($info)
    {
        if (!is_array($info['id'])) {
            $info['id'] = [$info['id']];
        }
        return $this->model->whereIn('id', $info['id'])->where('uid', $info['uid'])->delete();
    }
}
