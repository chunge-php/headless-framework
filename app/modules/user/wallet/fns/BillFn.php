<?php

namespace app\modules\user\wallet\fns;

use app\modules\user\wallet\model\Bill;
use support\Db;

class BillFn
{

    private $model;
    public function __construct(Bill $bill)
    {
        $this->model = $bill;
    }

    /**
     * Summary of index
     * @param mixed $info
     * @param mixed $limit
     * @param mixed $offset
     * @return array{list: array, total: int}
     */
    public function index($info, $limit = 1, $offset = 20)
    {

        $where = $this->model
            ->where('uid', $info['jwtUserId'])
            ->when(!empty($info['years']), fn($query) => $query->where('years', $info['years']))
            ->when(!empty($info['months']), fn($query) => $query->where('months', $info['months']))
            ->when(!empty($info['days']), fn($query) => $query->where('days', $info['days']))
            ->when(isset($info['send_type']) && $info['send_type'] > -1, fn($query) => $query->where('send_type', $info['send_type']))
            ->when(isset($info['bill_type']) && $info['bill_type'] > -1, fn($query) => $query->where('bill_type', $info['bill_type']))
            ->when(!empty($info['begin_time']) && !empty($info['end_time']), fn($query) => $query->whereBetween('created_at', [$info['begin_time'], $info['end_time']]));
        $total = $where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            $list  = $where
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();

            return ['total' => $total, 'list' => $list];
        }
    }

        /**
     * 并发安全的账单号生成（MySQL 原子序列；按纽约本地日历切日）
     * 格式：BILL-YYYYMMDD-000001
     */
    public  function nextBillNo(string $biz = 'BILL', int $padLen = 6, string $tz = 'America/New_York'): string
    {
        // 纽约本地日期（含 DST）
        $ymd = (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Ymd');

        // 原子自增：首次插入=1；存在则 val=val+1，并用 LAST_INSERT_ID 拿到新值
        Db::update("
        INSERT INTO bill_seqs (biz, ymd, val, updated_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            val = LAST_INSERT_ID(val + 1),
            updated_at = NOW()
    ", [$biz, $ymd]);
        // 直接用 PDO 取 lastInsertId（等价于 SELECT LAST_INSERT_ID()）
        $seq = (int) Db::connection()->getPdo()->lastInsertId();

        // 统一成更易读的外显编号：BIZ-YYYYMMDD-XXXXXX
        return sprintf('%s%s%0' . $padLen . 'd', $biz, $ymd, $seq);
    }
}
