<?php

namespace app\modules\analysis\fns;

use app\modules\game\model\GameLog;
use app\modules\jobTask\model\JobDefinition;
use app\modules\sms\body\model\SmsBatchLot;
use app\modules\sms\body\model\SmsChannelLog;
use app\modules\user\addressbook\model\AddressBook;
use app\modules\user\wallet\model\Wallet;
use app\modules\user\wallet\model\WalletTopupLog;
use support\Db;

class AnalysisFn
{
    protected $smsBatchLot;
    protected $smsChannelLog;
    protected $wallet;
    protected $walletTopupLog;
    protected $addressBook;
    protected $jobDefinition;
    protected $gameLog;

    public function __construct(
        SmsBatchLot $smsBatchLot,
        SmsChannelLog $smsChannelLog,
        Wallet $wallet,
        WalletTopupLog $walletTopupLog,
        AddressBook $addressBook,
        JobDefinition $jobDefinition,
        GameLog $gameLog
    ) {
        $this->smsBatchLot = $smsBatchLot;
        $this->smsChannelLog = $smsChannelLog;
        $this->wallet = $wallet;
        $this->walletTopupLog = $walletTopupLog;
        $this->addressBook = $addressBook;
        $this->jobDefinition = $jobDefinition;
        $this->gameLog = $gameLog;
    }

    public function overview($info): array
    {
        $uid = (int)$info['jwtUserId'];
        $start = $info['start_date'];
        $end = $info['end_date'];
        [$prevStart, $prevEnd] = $this->prevEqualPeriod($start, $end);

        $balance = $this->wallet->where('uid', $uid)->value('balance');
        $balance = $balance === null ? '0.00' : $this->money((string)$balance);

        $monthCur = (float)$this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end])
            ->sum('total_money');
        $monthPrev = (float)$this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$prevStart, $prevEnd])
            ->sum('total_money');

        $sentCur = (int)$this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end])
            ->count();
        $sentPrev = (int)$this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$prevStart, $prevEnd])
            ->count();

        $contactCur = (int)$this->addressBook->where('uid', $uid)->count();
        $days = $this->diffDays($start, $end);
        $contactPrev = (int)$this->addressBook
            ->where('uid', $uid)
            ->where('created_at', '<', date('Y-m-d 00:00:00', strtotime("-{$days} days")))
            ->count();

        $taskRows = $this->jobDefinition
            ->where('uid', $uid)
            ->selectRaw('run_status, COUNT(*) as cnt')
            ->groupBy('run_status')
            ->get()
            ->toArray();
        $taskStat = ['running' => 0, 'success' => 0, 'failed' => 0, 'total' => 0];
        foreach ($taskRows as $r) {
            $r = (array)$r;
            $cnt = (int)$r['cnt'];
            $taskStat['total'] += $cnt;
            if ((int)$r['run_status'] === 2) $taskStat['running'] += $cnt;
            elseif ((int)$r['run_status'] === 1) $taskStat['success'] += $cnt;
            elseif (in_array((int)$r['run_status'], [3, 4])) $taskStat['failed'] += $cnt;
        }

        return [
            'balance' => $balance,
            'month_cost' => [
                'current' => $this->money($monthCur),
                'prev' => $this->money($monthPrev),
                'growth_rate' => $this->growthRate($monthCur, $monthPrev),
            ],
            'sent_total' => [
                'current' => $sentCur,
                'prev' => $sentPrev,
                'growth_rate' => $this->growthRate($sentCur, $sentPrev),
            ],
            'contact_total' => [
                'current' => $contactCur,
                'prev' => $contactPrev,
                'growth_rate' => $this->growthRate($contactCur, $contactPrev),
            ],
            'task_stat' => $taskStat,
        ];
    }

    public function statTrend($info): array
    {
        $uid = (int)$info['jwtUserId'];
        $start = $info['start_date'];
        $end = $info['end_date'];
        $granularity = $info['granularity'];

        $q = $this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end]);
        if (isset($info['subscribe_type']) && (int)$info['subscribe_type'] > -1) {
            $q = $q->where('subscribe_type', (int)$info['subscribe_type']);
        }
        if (isset($info['send_type']) && (int)$info['send_type'] > -1) {
            $q = $q->where('send_type', (int)$info['send_type']);
        }
        $rows = $q->selectRaw('DATE(created_at) as d, COUNT(*) as sent_count, SUM(success_total) as success_count, SUM(error_total) as failed_count, SUM(consume_number) as consume_number')
            ->groupBy('d')
            ->get()
            ->toArray();

        $dayMap = [];
        foreach ($rows as $r) {
            $r = (array)$r;
            $dayMap[$r['d']] = [
                'sent_count' => (int)$r['sent_count'],
                'success_count' => (int)$r['success_count'],
                'failed_count' => (int)$r['failed_count'],
                'consume_number' => (int)$r['consume_number'],
            ];
        }
        $buckets = $this->bucketize($start, $end, $granularity, $dayMap, [
            'sent_count' => 0, 'success_count' => 0, 'failed_count' => 0, 'consume_number' => 0,
        ]);

        $list = [];
        $totalSent = 0;
        $totalSuccess = 0;
        $totalFailed = 0;
        $totalConsume = 0;
        foreach ($buckets as $label => $agg) {
            $list[] = [
                'date' => $label,
                'sent_count' => $agg['sent_count'],
                'success_count' => $agg['success_count'],
                'failed_count' => $agg['failed_count'],
                'consume_number' => $agg['consume_number'],
            ];
            $totalSent += $agg['sent_count'];
            $totalSuccess += $agg['success_count'];
            $totalFailed += $agg['failed_count'];
            $totalConsume += $agg['consume_number'];
        }

        [$prevStart, $prevEnd] = $this->prevEqualPeriod($start, $end);
        $prevQ = $this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$prevStart, $prevEnd]);
        if (isset($info['subscribe_type']) && (int)$info['subscribe_type'] > -1) {
            $prevQ = $prevQ->where('subscribe_type', (int)$info['subscribe_type']);
        }
        if (isset($info['send_type']) && (int)$info['send_type'] > -1) {
            $prevQ = $prevQ->where('send_type', (int)$info['send_type']);
        }
        $prevTotalSent = (int)$prevQ->count();

        return [
            'list' => $list,
            'total_sent' => $totalSent,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'total_consume' => $totalConsume,
            'compare' => [
                'prev_total_sent' => $prevTotalSent,
                'growth_rate' => $this->growthRate($totalSent, $prevTotalSent),
            ],
        ];
    }

    public function costTrend($info): array
    {
        $uid = (int)$info['jwtUserId'];
        $start = $info['start_date'];
        $end = $info['end_date'];
        $granularity = $info['granularity'];

        $q = $this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end]);
        if (isset($info['send_type']) && (int)$info['send_type'] > -1) {
            $q = $q->where('send_type', (int)$info['send_type']);
        }
        $rows = $q->selectRaw("
                DATE(created_at) as d,
                SUM(total_money) as total_cost,
                SUM(CASE WHEN send_type = 0 THEN total_money ELSE 0 END) as sms_cost,
                SUM(CASE WHEN send_type = 1 THEN total_money ELSE 0 END) as mms_cost,
                SUM(CASE WHEN send_type = 2 THEN total_money ELSE 0 END) as email_cost
            ")
            ->groupBy('d')
            ->get()
            ->toArray();

        $dayMap = [];
        foreach ($rows as $r) {
            $r = (array)$r;
            $dayMap[$r['d']] = [
                'total_cost' => (float)$r['total_cost'],
                'sms_cost' => (float)$r['sms_cost'],
                'mms_cost' => (float)$r['mms_cost'],
                'email_cost' => (float)$r['email_cost'],
            ];
        }
        $buckets = $this->bucketize($start, $end, $granularity, $dayMap, [
            'total_cost' => 0.0, 'sms_cost' => 0.0, 'mms_cost' => 0.0, 'email_cost' => 0.0,
        ]);

        $list = [];
        $totalCost = 0.0;
        $totalSms = 0.0;
        $totalMms = 0.0;
        $totalEmail = 0.0;
        foreach ($buckets as $label => $agg) {
            $list[] = [
                'date' => $label,
                'total_cost' => $this->money($agg['total_cost']),
                'sms_cost' => $this->money($agg['sms_cost']),
                'mms_cost' => $this->money($agg['mms_cost']),
                'email_cost' => $this->money($agg['email_cost']),
            ];
            $totalCost += $agg['total_cost'];
            $totalSms += $agg['sms_cost'];
            $totalMms += $agg['mms_cost'];
            $totalEmail += $agg['email_cost'];
        }

        [$prevStart, $prevEnd] = $this->prevEqualPeriod($start, $end);
        $prevQ = $this->smsBatchLot
            ->where('uid', $uid)
            ->whereBetween(Db::raw('DATE(created_at)'), [$prevStart, $prevEnd]);
        if (isset($info['send_type']) && (int)$info['send_type'] > -1) {
            $prevQ = $prevQ->where('send_type', (int)$info['send_type']);
        }
        $prevTotalCost = (float)$prevQ->sum('total_money');

        return [
            'list' => $list,
            'total_cost' => $this->money($totalCost),
            'total_sms_cost' => $this->money($totalSms),
            'total_mms_cost' => $this->money($totalMms),
            'total_email_cost' => $this->money($totalEmail),
            'compare' => [
                'prev_total_cost' => $this->money($prevTotalCost),
                'growth_rate' => $this->growthRate($totalCost, $prevTotalCost),
            ],
        ];
    }

    public function kpiSparkline($info): array
    {
        $uid = (int)$info['jwtUserId'];
        $type = $info['type'];
        $end = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-29 days'));
        $days = [];
        for ($i = 0; $i < 30; $i++) {
            $days[] = date('Y-m-d', strtotime($start . " +{$i} days"));
        }

        $data = array_fill(0, 30, 0);

        if ($type === 'sent') {
            $rows = $this->smsBatchLot
                ->where('uid', $uid)
                ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end])
                ->selectRaw('DATE(created_at) as d, COUNT(*) as cnt')
                ->groupBy('d')
                ->pluck('cnt', 'd')
                ->toArray();
            foreach ($days as $i => $d) {
                $data[$i] = (int)($rows[$d] ?? 0);
            }
        } elseif ($type === 'monthCost') {
            $rows = $this->smsBatchLot
                ->where('uid', $uid)
                ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end])
                ->selectRaw('DATE(created_at) as d, SUM(total_money) as val')
                ->groupBy('d')
                ->pluck('val', 'd')
                ->toArray();
            foreach ($days as $i => $d) {
                $data[$i] = round((float)($rows[$d] ?? 0), 2);
            }
        } elseif ($type === 'contacts') {
            foreach ($days as $i => $d) {
                $data[$i] = (int)$this->addressBook
                    ->where('uid', $uid)
                    ->where('created_at', '<=', $d . ' 23:59:59')
                    ->count();
            }
        } elseif ($type === 'balance') {
            $rows = $this->walletTopupLog
                ->where('uid', $uid)
                ->whereBetween(Db::raw('DATE(created_at)'), [$start, $end])
                ->orderBy('id', 'asc')
                ->get(['created_at', 'after_balance_cents'])
                ->toArray();
            $dayLast = [];
            foreach ($rows as $r) {
                $r = (array)$r;
                $d = substr($r['created_at'], 0, 10);
                $dayLast[$d] = (float)$r['after_balance_cents'];
            }
            $currentBalance = (float)$this->wallet->where('uid', $uid)->value('balance');
            $last = $currentBalance;
            for ($i = 29; $i >= 0; $i--) {
                $d = $days[$i];
                if (isset($dayLast[$d])) {
                    $last = $dayLast[$d];
                    break;
                }
            }
            $running = null;
            foreach ($days as $i => $d) {
                if (isset($dayLast[$d])) {
                    $running = $dayLast[$d];
                } elseif ($running === null) {
                    $running = $last;
                }
                $data[$i] = round((float)$running, 2);
            }
        }

        return ['type' => $type, 'data' => $data];
    }

    public function gameStat($info): array
    {
        $uid = (int)$info['jwtUserId'];
        $participants = (int)$this->smsChannelLog
            ->where('uid', $uid)
            ->where('channel_type', 3)
            ->count();
        $winners = (int)$this->gameLog
            ->where('uid', $uid)
            ->where('result', '<>', '')
            ->where('result', '<>', '未中奖')
            ->count();
        $winRate = $participants > 0 ? round($winners * 100 / $participants, 1) : 0;
        $weeklyNew = (int)$this->smsChannelLog
            ->where('uid', $uid)
            ->where('channel_type', 3)
            ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime('-6 days')))
            ->count();
        return [
            'total_participants' => $participants,
            'total_winners' => $winners,
            'win_rate' => $winRate,
            'weekly_new' => $weeklyNew,
        ];
    }

    public function typeDistribution($info): array
    {
        $uid = (int)$info['jwtUserId'];
        $q = $this->smsBatchLot->where('uid', $uid);
        if (!empty($info['start_date']) && !empty($info['end_date'])) {
            $q = $q->whereBetween(Db::raw('DATE(created_at)'), [$info['start_date'], $info['end_date']]);
        }
        $rows = $q->selectRaw('send_type, SUM(consume_number) as cnt')
            ->groupBy('send_type')
            ->pluck('cnt', 'send_type')
            ->toArray();

        $types = [
            0 => ['name' => 'sms', 'label' => 'SMS'],
            1 => ['name' => 'mms', 'label' => 'MMS'],
            2 => ['name' => 'email', 'label' => 'Email'],
        ];
        $counts = [];
        $total = 0;
        foreach ($types as $key => $_) {
            $c = (int)($rows[$key] ?? 0);
            $counts[$key] = $c;
            $total += $c;
        }
        $items = [];
        foreach ($types as $key => $meta) {
            $c = $counts[$key];
            $pct = $total > 0 ? round($c * 100 / $total, 1) : 0;
            $items[] = [
                'name' => $meta['name'],
                'label' => $meta['label'],
                'count' => $c,
                'percent' => $pct,
            ];
        }
        return ['total' => $total, 'items' => $items];
    }

    public function recentActivity($info, $limit = 10, $offset = 0): array
    {
        $uid = (int)$info['jwtUserId'];
        $where = $this->smsBatchLot
            ->where('uid', $uid)
            ->select([
                'id', 'code', 'content', 'subject', 'status', 'total_money',
                'success_total', 'error_total', 'consume_number',
                'subscribe_type', 'send_type', 'created_at', 'updated_at',
            ]);
        $total = (int)$where->count();
        if ($total < 1) {
            return ['total' => 0, 'list' => []];
        }
        $rows = $where->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
        $typeMap = [0 => 'sms', 1 => 'mms', 2 => 'email'];
        $list = [];
        foreach ($rows as $r) {
            $r = (array)$r;
            $r['type'] = $typeMap[(int)$r['send_type']] ?? 'sms';
            $r['total_money'] = $this->money((string)($r['total_money'] ?? 0));
            $list[] = $r;
        }
        return ['total' => $total, 'list' => $list];
    }

    private function bucketize(string $start, string $end, string $granularity, array $dayMap, array $zero): array
    {
        $buckets = [];
        $cursor = strtotime($start);
        $endTs = strtotime($end);
        while ($cursor <= $endTs) {
            $d = date('Y-m-d', $cursor);
            $label = $this->bucketLabel($d, $granularity);
            if (!isset($buckets[$label])) {
                $buckets[$label] = $zero;
            }
            if (isset($dayMap[$d])) {
                foreach ($zero as $k => $_) {
                    $buckets[$label][$k] += $dayMap[$d][$k] ?? 0;
                }
            }
            $cursor += 86400;
        }
        return $buckets;
    }

    private function bucketLabel(string $date, string $granularity): string
    {
        if ($granularity === 'day') return $date;
        if ($granularity === 'month') return date('Y-m', strtotime($date));
        $ts = strtotime($date);
        $weekday = (int)date('N', $ts);
        return date('Y-m-d', $ts - ($weekday - 1) * 86400);
    }

    private function prevEqualPeriod(string $start, string $end): array
    {
        $days = $this->diffDays($start, $end);
        $prevEnd = date('Y-m-d', strtotime($start) - 86400);
        $prevStart = date('Y-m-d', strtotime($prevEnd) - ($days - 1) * 86400);
        return [$prevStart, $prevEnd];
    }

    private function diffDays(string $start, string $end): int
    {
        return (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
    }

    private function growthRate($current, $prev): float
    {
        $current = (float)$current;
        $prev = (float)$prev;
        if ($prev <= 0) return 0.0;
        return round(($current - $prev) * 100 / $prev, 1);
    }

    private function money($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }
}
