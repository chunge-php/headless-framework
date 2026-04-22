<?php

namespace app\modules\analysis\controllers;

use app\modules\analysis\fns\AnalysisFn;
use support\Request;

class AnalysisController
{
    public function overview(Request $request, AnalysisFn $fn)
    {
        $all = $request->all();
        if (empty($all['start_date']) || empty($all['end_date'])) {
            return error('params_missing', info_err);
        }
        if ($all['start_date'] > $all['end_date']) {
            return error('date_range_invalid', 404);
        }
        $all['jwtUserId'] = $request->jwtUserId;
        return success($fn->overview($all));
    }

    public function statTrend(Request $request, AnalysisFn $fn)
    {
        $all = $request->all();
        if (empty($all['start_date']) || empty($all['end_date']) || empty($all['granularity'])) {
            return error('params_missing', info_err);
        }
        if ($all['start_date'] > $all['end_date']) {
            return error('date_range_invalid', 404);
        }
        if ($this->diffDays($all['start_date'], $all['end_date']) > 366) {
            return error('date_range_too_large', 404);
        }
        if (!in_array($all['granularity'], ['day', 'week', 'month'])) {
            return error('granularity_invalid', info_err);
        }
        $all['jwtUserId'] = $request->jwtUserId;
        return success($fn->statTrend($all));
    }

    public function costTrend(Request $request, AnalysisFn $fn)
    {
        $all = $request->all();
        if (empty($all['start_date']) || empty($all['end_date']) || empty($all['granularity'])) {
            return error('params_missing', info_err);
        }
        if ($all['start_date'] > $all['end_date']) {
            return error('date_range_invalid', 404);
        }
        if ($this->diffDays($all['start_date'], $all['end_date']) > 366) {
            return error('date_range_too_large', 404);
        }
        if (!in_array($all['granularity'], ['day', 'week', 'month'])) {
            return error('granularity_invalid', info_err);
        }
        $all['jwtUserId'] = $request->jwtUserId;
        return success($fn->costTrend($all));
    }

    public function kpiSparkline(Request $request, AnalysisFn $fn)
    {
        $all = $request->all();
        $type = $all['type'] ?? '';
        if (!in_array($type, ['balance', 'monthCost', 'sent', 'contacts'])) {
            return error('type_invalid', info_err);
        }
        $all['jwtUserId'] = $request->jwtUserId;
        return success($fn->kpiSparkline($all));
    }

    public function gameStat(Request $request, AnalysisFn $fn)
    {
        $all = ['jwtUserId' => $request->jwtUserId];
        return success($fn->gameStat($all));
    }

    public function typeDistribution(Request $request, AnalysisFn $fn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($fn->typeDistribution($all));
    }

    public function recentActivity(Request $request, AnalysisFn $fn)
    {
        $all = $request->all();
        $all['jwtUserId'] = $request->jwtUserId;
        return success($fn->recentActivity($all, $request->limit, $request->offset));
    }

    private function diffDays(string $start, string $end): int
    {
        return (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
    }
}
