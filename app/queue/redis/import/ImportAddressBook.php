<?php

namespace app\queue\redis\import;

use app\modules\templat\model\Templat;
use Webman\RedisQueue\Consumer;
use support\Log;
use app\modules\user\addressbook\model\AddressBook;
use app\modules\myclass\TemplateImport;

class ImportAddressBook implements Consumer
{
    public $queue = 'import-address-book';
    public $connection = 'default';
    protected $arr;
    protected $addressBook;
    protected $templat;

    public function consume($data)
    {
        $file = $data['file'];
        $preset = $data['preset'];
        $jwtUserId = $data['jwtUserId'];
        $created_at = $data['created_at'];
        $tags_id = $data['tags_id'];
        colorText("导入联系人 {$jwtUserId} ID" . getMillisecondTime());
        $this->addressBook =  new AddressBook();
        $this->templat =  new Templat();
        feature('batchLog.update', ['id' => $data['batchs_id'], 'status' => state_two]);
        $fild_map = $this->getFiledMap($preset, $jwtUserId, $created_at);
        if (empty($fild_map)) tryFun('import_error', import_error);
        try {
            $imp = new TemplateImport([
                'table'         => $fild_map['table'],
                'map_by_index'  => true, // 开启“按下标”
                'index_fields'  => $fild_map['index_fields'],
                'skip_header'   => true, // 第一行当标题跳过；若第一行就是数据改成 false
                'afterMap'      => $fild_map['afterMap'] ?? null,
                'upsert'       => $fild_map['upsert'] ?? null,
                'row_state' => $fild_map['row_state'] ?? false
            ]);

            $res = $imp->import($file);
            if ($preset == 'linkman' && !empty($tags_id)) {
                $target_id = $this->addressBook->where('created_at', $created_at)->pluck('id')->toArray();
                feature('tag.createTagBookBatch', $jwtUserId, addressbook_str, $target_id, $tags_id);
            }
            colorText("导入联系人总数 {$res['total']} ");
            colorText("成功总数 {$res['ok']} ");
            feature('batchLog.update', ['id' => $data['batchs_id'], 'status' => state_one, 'total' => $res['total'] ?? 0, 'success' => $data['total'], 'describe' => $res['error_file'] ?? '', 'fail' => $res['fail'] ?? 0]);
        } catch (\Exception $e) {
            debugMessage([$e->getMessage(), $e->getLine()],'导入联系人异常');
            feature('batchLog.update', ['id' => $data['batchs_id'], 'status' => state_three]);
            $res = null;
        }
    }
    public function getFiledMap($preset, $jwtUserId, $created_at)
    {
        $data = [];
        switch ($preset) {
            case 'linkman':
                $data = [
                    'index_fields' => [
                        'last_name',        // 第 0 列
                        'first_name',         // 第 1 列
                        'phone',             // 第 2 列
                        'email',             // 第 3 列
                        'birthday',          // 第 4 列
                    ],
                    'table' => $this->addressBook,
                    'afterMap' => function (array $m) use ($jwtUserId, $created_at) { // 清洗（可选）
                        $m['uid'] = $jwtUserId;
                        $m['phone']   = phone_to_digits($m['phone'] ?? '');
                        $m['birthday'] = toYmdOrEmpty($m['birthday'] ?? '');
                        $m['month_day'] = !empty($m['birthday']) ? substr($m['birthday'], 5) : '';
                        $m['created_at'] = $created_at;
                        return $m;
                    },
                    'upsert' => [
                        'uniqueBy' => ['uid', 'phone'],     // 复合唯一键
                        'update'   => ['last_name', 'first_name', 'email', 'birthday', 'created_at'] // 命中则更新这些列
                    ],
                ];
                break;
            case 'sms_templats':
                $data = [
                    'index_fields' => [
                        'name',        // 第 0 列
                        'title',         // 第 1 列
                        'type',             // 第 2 列
                        'content',             // 第 3 列
                        'sort',          // 第 4 列
                    ],
                    'table' => $this->templat,
                    'afterMap' => function (array $m) use ($jwtUserId) { // 清洗（可选）
                        $m['uid'] = $jwtUserId;
                        $m['type']   = (int)$m['type'];
                        $m['sort'] = (int)$m['sort'];
                        return $m;
                    },
                    'upsert' => [
                        'uniqueBy' => ['uid', 'name', 'type'],     // 复合唯一键
                        'update'   => ['title', 'content', 'sort'] // 命中则更新这些列
                    ]
                ];
                break;
            default:
                $data = [];
                break;
        }
        return $data;
    }
    public function test($file)
    {
        $imp = new TemplateImport([
            'table'         => 'members',
            'map_by_index'  => true, // 开启“按下标”
            'index_fields'  => [
                'first_name',        // 第 0 列
                'last_name',         // 第 1 列
                'phone',             // 第 2 列
                'email',             // 第 3 列
                'birthday',          // 第 4 列
                'balance',           // 第 5 列
                'points',            // 第 6 列
                'remarks',           // 第 7 列
                'referrer',          // 第 8 列
            ],
            'skip_header'   => true, // 第一行当标题跳过；若第一行就是数据改成 false
            'afterMap'      => function (array $m) { // 清洗（可选）
                $m['phone']   = TemplateImport::normalizePhone($m['phone'] ?? '');
                $m['balance'] = TemplateImport::normalizeMoney($m['balance'] ?? '');
                $m['birthday'] = TemplateImport::normalizeDate($m['birthday'] ?? '');
                return $m;
            },
        ]);

        $res = $imp->import($file);
        return $res;
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

        Log::error("导入联系人队列失败：" . $e->getMessage(), 'red');
    }
}
